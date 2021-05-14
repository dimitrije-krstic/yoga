<?php
declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    private const DEFAULT_USER_LIMIT_PER_PAGE = 24;

    private $paginator;

    public function __construct(ManagerRegistry $registry, PaginatorInterface $paginator)
    {
        parent::__construct($registry, User::class);
        $this->paginator = $paginator;
    }

    public function getMasterAdminUser(): ?User
    {
        return $this->createQueryBuilder('u')
            ->select('u')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_MASTER%')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(UserInterface $user, string $newEncodedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newEncodedPassword);
        $this->_em->persist($user);
        $this->_em->flush();
    }

    public function getActiveUser(string $slug, bool $showAll): ?User
    {
        $queryBuilder = $this->createQueryBuilder('u')
            ->select('u')
            ->andWhere('u.deletedAt is null')
            ->andWhere('u.slug = :slug')
            ->setParameter('slug', $slug)
        ;

        if (!$showAll) {
            $queryBuilder->andWhere('u.accountPubliclyVisible = 1');
        }

        return $queryBuilder
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function getPaginatedUsers(bool $showAll = true, int $page = 1, ?string $queryTerm = null): PaginationInterface
    {
        $queryBuilder = $this->createQueryBuilder('u')
            ->select('u')
            ->andWhere('u.acceptedTermsOfUse = 1')
            ->andWhere('u.deletedAt is null')
            ->addOrderBy('u.createdAt', 'DESC')
        ;

        if (!$showAll) {
            $queryBuilder->andWhere('u.accountPubliclyVisible = 1');
        }

        if ($queryTerm) {
            $this->addQueryConditionToUserSearchBuilder($queryTerm, $queryBuilder);
        }

        return $this->paginator->paginate(
            $queryBuilder,
            $page,
            self::DEFAULT_USER_LIMIT_PER_PAGE
        );
    }

    public function getPaginatedFollowers(User $user, int $page, ?string $queryTerm = null): PaginationInterface
    {
        $queryBuilder = $this->createQueryBuilder('u')
            ->select('u')
            ->leftJoin('u.following', 'f')
            ->andWhere('u.deletedAt IS NULL')
            ->andWhere('f.id = :userId')
            ->setParameter('userId', $user->getId())
            ->addOrderBy('u.name', 'ASC');

        if ($queryTerm) {
            $this->addQueryConditionToUserSearchBuilder($queryTerm, $queryBuilder);
        }

        return $this->paginator->paginate(
            $queryBuilder,
            $page,
            self::DEFAULT_USER_LIMIT_PER_PAGE
        );
    }

    public function getPaginatedFollowees(User $user, int $page, ?string $queryTerm = null): PaginationInterface
    {
        $queryBuilder = $this->createQueryBuilder('u')
            ->select('u.id, u.slug, u.name, u.profileImage as image, u.isPatron as patron')
            ->leftJoin('u.followedBy', 'fb')
            ->andWhere('u.deletedAt IS NULL')
            ->andWhere('fb.id = :userId')
            ->setParameter('userId', $user->getId())
            ->addOrderBy('u.name', 'ASC');

        if ($queryTerm) {
            $this->addQueryConditionToUserSearchBuilder($queryTerm, $queryBuilder);
        }

        return $this->paginator->paginate(
            $queryBuilder,
            $page,
            self::DEFAULT_USER_LIMIT_PER_PAGE
        );
    }

    public function isMemberFollowedByUser(User $user, User $member): ?User
    {
        return $this->createQueryBuilder('u')
            ->select('u')
            ->leftJoin('u.followedBy', 'fb')
            ->where('u.id = :memberId')
            ->setParameter('memberId', $member->getId())
            ->andWhere('fb.id = :userId')
            ->setParameter('userId', $user->getId())
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function getPaginatedDeletedUsers(int $page): PaginationInterface
    {
        $queryBuilder = $this->createQueryBuilder('u')
            ->select('u')
            ->andWhere('u.deletedAt is not null')
            ->addOrderBy('u.deletedAt', 'DESC')
        ;

        return $this->paginator->paginate(
            $queryBuilder,
            $page,
            self::DEFAULT_USER_LIMIT_PER_PAGE
        );
    }

    public function getAuthorNamesForPosts($postIds): array
    {
        if (empty($postIds)) {
            return [];
        }

        return $this->createQueryBuilder('u')
            ->select('u.name, u.accountPubliclyVisible as public, p.id, p.webPostAuthorName')
            ->leftJoin('u.posts', 'p')
            ->andWhere('p.id IN (:postIds)')
            ->setParameter('postIds', $postIds, Connection::PARAM_INT_ARRAY)
            ->getQuery()
            ->getArrayResult();
    }

    public function getAuthorImagesForEvents($events): array
    {
        if (empty($events)) {
            return [];
        }

        return $this->createQueryBuilder('u')
            ->select('u.accountPubliclyVisible as public, u.profileImage, u.slug, e.id')
            ->leftJoin('u.events', 'e')
            ->andWhere('e.id IN (:eventIds)')
            ->setParameter('eventIds', $events, Connection::PARAM_INT_ARRAY)
            ->getQuery()
            ->getArrayResult();
    }

    private function addQueryConditionToUserSearchBuilder(string $queryTerm, QueryBuilder $queryBuilder): void
    {
        $terms = array_filter(explode(' ', $queryTerm));

        $queryParts = [];
        foreach ($terms as $key => $term) {
            $queryParts[] = ' u.name LIKE :term' .$key. ' OR u.currentLocation LIKE :term'.$key. ' ';
        }
        $query = implode(' OR ', $queryParts);

        $queryBuilder->andWhere($query);
        foreach ($terms as $key => $term) {
            $queryBuilder->setParameter('term'.$key, '%' .$term. '%');
        }
    }
}
