<?php
declare(strict_types=1);

namespace App\Repository;

use App\Entity\MessageThread;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;

/**
 * @method MessageThread|null find($id, $lockMode = null, $lockVersion = null)
 * @method MessageThread|null findOneBy(array $criteria, array $orderBy = null)
 * @method MessageThread[]    findAll()
 * @method MessageThread[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MessageThreadRepository extends ServiceEntityRepository
{
    private const THREADS_LIMIT = 8;
    private const NOTIFICATIONS_LIMIT = 16;

    private $paginator;

    public function __construct(ManagerRegistry $registry, PaginatorInterface $paginator)
    {
        parent::__construct($registry, MessageThread::class);
        $this->paginator = $paginator;
    }

    public function getPaginatedPublicForumThreads(int $page, ?string $query): PaginationInterface
    {
        $queryBuilder = $this->createQueryBuilderForTabs($query, true);
        $queryBuilder->addSelect('u.accountPubliclyVisible as visible')
            ->leftJoin('t.createdBy', 'u')
            ->andWhere('t.type = 3');

        return $this->paginator->paginate(
            $queryBuilder,
            $page,
            self::THREADS_LIMIT
        );
    }

    public function getMembersLastNotifications(User $member): array
    {
        $queryBuilder = $this->createQueryBuilderForTabs(null);

        return $queryBuilder
            ->addSelect('m.content')
            ->leftJoin('t.messages', 'm')
            ->leftJoin('t.createdBy', 'u')
            ->andWhere('t.createdBy = :member')
            ->setParameter('member', $member)
            ->andWhere('t.type = '. MessageThread::TYPE_NOTIFICATION)
            ->setMaxResults(self::NOTIFICATIONS_LIMIT)
            ->getQuery()
            ->getResult();
    }

    public function getPaginatedThreads(string $tab, User $user, int $page, ?string $query): PaginationInterface
    {
        if ($tab === 'sent') {
            return $this->getSentThreads($user, $page, $query);
        }

        if ($tab === 'forum') {
            return $this->getUsersForumThreads($user, $page, $query);
        }

        return $this->getReceivedThreads($tab, $user, $page, $query);
    }

    public function countUnreadReceivedThreads(string $tab, User $user): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('count(DISTINCT t.id)')
            ->leftJoin('t.messages', 'm')
            ->andWhere('t.type = 1')
            ->andWhere('t.receiver = :receiver')
            ->setParameter('receiver', $user->getId())
            ->andWhere('m.read = 0')
            ->andWhere('m.createdBy != '.$user->getId())
            ->andWhere('t.spam = 0')
            ->andWhere('t.admin = '.($tab === 'admin' ? 1 : 0))
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countUnreadSentThreads(User $user): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('count(DISTINCT t.id)')
            ->leftJoin('t.messages', 'm')
            ->andWhere('t.createdBy = :created')
            ->setParameter('created', $user->getId())
            ->andWhere('t.type = 1')
            ->andWhere('m.read = 0')
            ->andWhere('m.createdBy != '.$user->getId())
            ->andWhere('t.spam = 0')
            ->andWhere('t.admin = 0')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countOwnUnreadForumThreads(string $type, User $user): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('count(DISTINCT t.id)')
            ->leftJoin('t.messages', 'm')
            ->andWhere('t.type = '.MessageThread::TYPE_FORUM)
            ->andWhere('t.createdBy = :created')
            ->setParameter('created', $user->getId())
            ->andWhere('m.read = 0')
            ->andWhere('m.createdBy != '.$user->getId())
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return int[]
     */
    public function getUnreadUpdateThreadsIds(
        array $threadIds,
        User $user,
        ?\DateTime $lastVisited
    ): array {
        if (empty($threadIds) || $lastVisited === null) {
            return [];
        }

        $result = $this->createQueryBuilder('t')
            ->select('t.id')
            ->leftJoin('t.messages', 'm')
            ->andWhere('t.id IN (:ids)')
            ->setParameter('ids', $threadIds, Connection::PARAM_INT_ARRAY)
            ->andWhere('m.createdBy != '.$user->getId())
            ->andWhere('m.createdAt > :lastVisited')
            ->setParameter('lastVisited', $lastVisited)
            ->distinct()
            ->getQuery()
            ->getResult();

        return array_column($result, 'id');
    }

    public function getOnlyUnreadThreadsIds(string $activeTab, array $threadIds, User $user): array
    {
        if (empty($threadIds)) {
            return [];
        }

        $qb = $this->createQueryBuilder('t')
            ->select('t.id')
            ->leftJoin('t.messages', 'm')
            ->andWhere('t.id IN (:ids)')
            ->setParameter('ids', $threadIds, Connection::PARAM_INT_ARRAY)
            ->andWhere('m.read = 0')
            ->andWhere('m.createdBy != '.$user->getId());

        if ($activeTab === 'forum') {
            $qb->andWhere('t.createdBy = '.$user->getId());
        }

        $result = $qb
            ->distinct()
            ->getQuery()
            ->getResult();

        return array_column($result, 'id');
    }

    private function getReceivedThreads(string $tab, User $user, int $page, ?string $query): PaginationInterface
    {
        $queryBuilder = $this->createQueryBuilderForTabs($query);
        $queryBuilder->leftJoin('t.createdBy', 'u')
            ->andWhere('t.receiver = :receiver')
            ->setParameter('receiver', $user->getId());

        $tab === 'spam' ? $queryBuilder->andWhere('t.spam = 1') :  $queryBuilder->andWhere('t.spam = 0');
        $tab === 'admin' ? $queryBuilder->andWhere('t.admin = 1') :  $queryBuilder->andWhere('t.admin = 0');

        return $this->paginator->paginate(
            $queryBuilder,
            $page,
            self::THREADS_LIMIT
        );
    }

    private function getSentThreads(User $user, int $page, ?string $query): PaginationInterface
    {
        $queryBuilder = $this->createQueryBuilderForTabs($query);
        $queryBuilder->leftJoin('t.receiver', 'u')
            ->andWhere('t.createdBy = :createdBy')
            ->setParameter('createdBy', $user->getId())
            ->andWhere('t.type = 1');

        return $this->paginator->paginate(
            $queryBuilder,
            $page,
            self::THREADS_LIMIT
        );
    }

    private function getUsersForumThreads(User $user, int $page, ?string $query): PaginationInterface
    {
        $queryBuilder = $this->createQueryBuilderForTabs($query);
        $queryBuilder->leftJoin('t.createdBy', 'u')
            ->andWhere('t.createdBy = :createdBy')
            ->setParameter('createdBy', $user->getId())
            ->andWhere('t.type = '.MessageThread::TYPE_FORUM);

        return $this->paginator->paginate(
            $queryBuilder,
            $page,
            self::THREADS_LIMIT
        );
    }

    private function createQueryBuilderForTabs(?string $queryTerm, bool $orderByCreatedAt = false): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('t')
            ->select('t.id, t.updatedAt, t.subject, u.slug, u.name, u.profileImage, SIZE(t.messages) as messageCount')
            ->orderBy($orderByCreatedAt ? 't.createdAt' : 't.updatedAt', 'DESC');

        if ($queryTerm) {
            $terms = array_filter(explode(' ', $queryTerm));

            $queryParts = [];
            foreach ($terms as $key => $term) {
                $queryParts[] = ' t.subject LIKE :term'. $key . ' ';
            }
            $query = implode(' OR ', $queryParts);

            $queryBuilder->andWhere($query);
            foreach ($terms as $key => $term) {
                $queryBuilder->setParameter('term'.$key, '%' .$term. '%');
            }
        }

        return $queryBuilder;
    }
}
