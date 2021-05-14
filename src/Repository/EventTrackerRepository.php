<?php
declare(strict_types=1);

namespace App\Repository;

use App\Entity\EventTracker;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method EventTracker|null find($id, $lockMode = null, $lockVersion = null)
 * @method EventTracker|null findOneBy(array $criteria, array $orderBy = null)
 * @method EventTracker[]    findAll()
 * @method EventTracker[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EventTrackerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventTracker::class);
    }

    public function getUpdateFlagsForBookmarkedEvents(array $eventIds, User $user): array
    {
        if (empty($eventIds)) {
            return [];
        }

        $result = $this->createQueryBuilder('et')
            ->select('e.id, et.updatedAt, max(c.createdAt) as lastComment')
            ->join('et.event', 'e')
            ->join('e.comments', 'c')
            ->andWhere('et.user = '.$user->getId())
            ->andWhere('et.event IN (:eventIds)')
            ->setParameter('eventIds', $eventIds, Connection::PARAM_INT_ARRAY)
            ->andWhere('c.author != '.$user->getId())
            ->groupBy('e.id')
            ->getQuery()
            ->getArrayResult();

        $updates = [];
        foreach ($result as $eventWithComments) {
            $updates[$eventWithComments['id']] = $eventWithComments['updatedAt'] < new \DateTime($eventWithComments['lastComment']);
        }

        return $updates;
    }
}
