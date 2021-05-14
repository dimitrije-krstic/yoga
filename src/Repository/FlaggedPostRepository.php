<?php
declare(strict_types=1);

namespace App\Repository;

use App\Entity\FlaggedPost;
use App\Entity\Post;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;

/**
 * @method FlaggedPost|null find($id, $lockMode = null, $lockVersion = null)
 * @method FlaggedPost|null findOneBy(array $criteria, array $orderBy = null)
 * @method FlaggedPost[]    findAll()
 * @method FlaggedPost[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FlaggedPostRepository extends ServiceEntityRepository
{
    private const DEFAULT_POSTS_PER_PAGE = 24;

    private $paginator;

    public function __construct(ManagerRegistry $registry, PaginatorInterface $paginator)
    {
        parent::__construct($registry, FlaggedPost::class);
        $this->paginator = $paginator;
    }

    public function isPostFlaggedAsInappropriateByUser(?User $user,Post $post): bool
    {
        if ($user === null) {
            return false;
        }

        $report = $this->createQueryBuilder('f')
            ->select('f')
            ->andWhere('f.post = :post')
            ->setParameter('post', $post)
            ->andWhere('f.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();

        return $report ? true : false;
    }

    public function getPaginatedFlaggedPosts(int $page): PaginationInterface
    {
        $queryBuilder = $this->createQueryBuilder('f')
            ->select(
                'p.id as postId, 
                p.slug as postSlug, 
                a.slug as authorSlug, 
                a.email as authorEmail, 
                f.id as reportId, 
                f.createdAt as flagCreatedAt,
                f.updatedAt as flagUpdatedAt,
                f.status as flagStatus, 
                f.reason as flagReason,
                m.email as reportingMemberEmail'
            )
            ->leftJoin('f.post', 'p')
            ->leftJoin('p.author', 'a')
            ->leftJoin('f.user', 'm')
            ->andWhere('f.status = 1 OR f.status = 3')
            ->orderBy('p.id', 'DESC')
        ;

        return $this->paginator->paginate(
            $queryBuilder,
            $page,
            self::DEFAULT_POSTS_PER_PAGE
        );
    }
}
