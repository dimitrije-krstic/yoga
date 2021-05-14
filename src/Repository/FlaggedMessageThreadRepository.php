<?php
declare(strict_types=1);

namespace App\Repository;

use App\Entity\FlaggedMessageThread;
use App\Entity\MessageThread;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;

/**
 * @method FlaggedMessageThread|null find($id, $lockMode = null, $lockVersion = null)
 * @method FlaggedMessageThread|null findOneBy(array $criteria, array $orderBy = null)
 * @method FlaggedMessageThread[]    findAll()
 * @method FlaggedMessageThread[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FlaggedMessageThreadRepository extends ServiceEntityRepository
{
    private const DEFAULT_THREADS_PER_PAGE = 24;

    private $paginator;

    public function __construct(ManagerRegistry $registry, PaginatorInterface $paginator)
    {
        parent::__construct($registry, FlaggedMessageThread::class);
        $this->paginator = $paginator;
    }

    public function isThreadFlaggedAsInappropriateByUser(?User $user, ?MessageThread $thread): bool
    {
        if ($user === null || $thread === null) {
            return false;
        }

        $report = $this->createQueryBuilder('f')
            ->select('f')
            ->andWhere('f.thread = :thread')
            ->setParameter('thread', $thread)
            ->andWhere('f.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();

        return $report ? true : false;
    }

    public function getPaginatedFlaggedThreads(int $page): PaginationInterface
    {
        $queryBuilder = $this->createQueryBuilder('f')
            ->select(
                ' f.id as reportId, 
                t.id as threadId, 
                a.slug as authorSlug, 
                a.email as authorEmail, 
                f.createdAt as flagCreatedAt,
                f.updatedAt as flagUpdatedAt,
                f.status as flagStatus, 
                f.reason as flagReason,
                m.email as reportingMemberEmail'
            )
            ->leftJoin('f.thread', 't')
            ->leftJoin('t.createdBy', 'a')
            ->leftJoin('f.user', 'm')
            ->andWhere('f.status = 1')
            ->addOrderBy('f.createdAt', 'DESC')
        ;

        return $this->paginator->paginate(
            $queryBuilder,
            $page,
            self::DEFAULT_THREADS_PER_PAGE
        );
    }
}
