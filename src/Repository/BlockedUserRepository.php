<?php
declare(strict_types=1);

namespace App\Repository;

use App\Entity\BlockedUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;

/**
 * @method BlockedUser|null find($id, $lockMode = null, $lockVersion = null)
 * @method BlockedUser|null findOneBy(array $criteria, array $orderBy = null)
 * @method BlockedUser[]    findAll()
 * @method BlockedUser[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BlockedUserRepository extends ServiceEntityRepository
{
    private const DEFAULT_USER_LIMIT_PER_PAGE = 24;

    private $paginator;

    public function __construct(ManagerRegistry $registry, PaginatorInterface $paginator)
    {
        parent::__construct($registry, BlockedUser::class);
        $this->paginator = $paginator;
    }

    public function getPaginatedBlockedUsers(int $page): PaginationInterface
    {
        $queryBuilder = $this->createQueryBuilder('u')
            ->select('u')
            ->addOrderBy('u.blockedAt', 'DESC')
        ;

        return $this->paginator->paginate(
            $queryBuilder,
            $page,
            self::DEFAULT_USER_LIMIT_PER_PAGE
        );
    }
}
