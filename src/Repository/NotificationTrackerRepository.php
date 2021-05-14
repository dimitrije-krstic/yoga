<?php
declare(strict_types=1);

namespace App\Repository;

use App\Entity\MessageThread;
use App\Entity\NotificationTracker;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method NotificationTracker|null find($id, $lockMode = null, $lockVersion = null)
 * @method NotificationTracker|null findOneBy(array $criteria, array $orderBy = null)
 * @method NotificationTracker[]    findAll()
 * @method NotificationTracker[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NotificationTrackerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NotificationTracker::class);
    }

    public function getMemberIdsWithUnreadNotifications(array $memberIds, User $user): array
    {
        if (empty($memberIds)) {
            return [];
        }

        $results = $this->createQueryBuilder('nt')
            ->select('u.id, nt.updatedAt as lastVisit, max(t.updatedAt) as lastUpdate')
            ->andWhere('nt.follower = '.$user->getId())
            ->andWhere('nt.followed in (:ids)')
            ->setParameter('ids', $memberIds, Connection::PARAM_INT_ARRAY)
            ->leftJoin('nt.followed', 'u')
            ->leftJoin('u.threads', 't')
            ->andWhere('t.type = '.MessageThread::TYPE_NOTIFICATION)
            ->groupBy('u.id')
            ->getQuery()
            ->getResult();

        $ids = [];
        foreach ($results as $result) {
            if (new \DateTime($result['lastUpdate']) > $result['lastVisit']) {
                $ids[] = $result['id'];
            }
        }

        return $ids;
    }

    public function usersAreConnected(User $member, User $user): bool
    {
        $results = $this->createQueryBuilder('nt')
            ->select('count(nt.id)')
            ->andWhere('(nt.follower = :member AND nt.followed = :user) OR (nt.follower = :user AND nt.followed = :member)')
            ->setParameter('member', $member)
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        return $results > 0;
    }
}
