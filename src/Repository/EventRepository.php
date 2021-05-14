<?php
declare(strict_types=1);

namespace App\Repository;

use App\Entity\Event;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;

/**
 * @method Event|null find($id, $lockMode = null, $lockVersion = null)
 * @method Event|null findOneBy(array $criteria, array $orderBy = null)
 * @method Event[]    findAll()
 * @method Event[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EventRepository extends ServiceEntityRepository
{
    private const PUBLIC_VIEW_EVENT_LIST_LIMIT = 12;
    public const MORE_EVENTS_FROM_AUTHOR_LIMIT = 2;

    private PaginatorInterface $paginator;

    public function __construct(ManagerRegistry $registry, PaginatorInterface $paginator)
    {
        parent::__construct($registry, Event::class);
        $this->paginator = $paginator;
    }

    public function getPaginatedEvents(
        int $page,
        int $category,
        ?string $from,
        ?string $to,
        ?User $member
    ): PaginationInterface {
        $queryBuilder = $this->createQueryBuilderForViewPages($category, $member);

        $fromDate = $from ? (new \DateTime($from))->setTime(0, 0, 0) : null;
        $toDate = $to ? (new \DateTime($to))->setTime(24, 59, 59) : null;

        $now = new \DateTime();
        if ($fromDate && $fromDate > $now) {
            $now = $fromDate;
        }

        $queryBuilder
            ->andWhere('e.published IS NOT null')
            ->andWhere('e.cancelled IS null')
            ->andWhere('e.start > :now')
            ->setParameter('now', $now)
            ->addOrderBy('e.start', 'ASC');

        if ($toDate && $toDate > $now) {
            $queryBuilder->andWhere('e.start < :to')
                ->setParameter('to', $toDate);
        }

        return $this->paginator->paginate(
            $queryBuilder,
            $page,
            self::PUBLIC_VIEW_EVENT_LIST_LIMIT
        );
    }

    public function getPaginatedUserEvents(
        User $user,
        int $page,
        int $category,
        ?string $from,
        ?string $to
    ): PaginationInterface {
        $queryBuilder = $this->createQueryBuilderForViewPages($category, $user);

        $queryBuilder
            ->addOrderBy('e.start', 'DESC');

        $this->setTimeSpanToQueryBuilder(
            $from ? new \DateTime($from) : null,
            $to ? new \DateTime($to) : null,
            $queryBuilder
        );

        return $this->paginator->paginate(
            $queryBuilder,
            $page,
            self::PUBLIC_VIEW_EVENT_LIST_LIMIT
        );
    }

    public function getPaginatedBookmarkedEvents(
        User $user,
        int $page,
        int $category,
        ?string $from,
        ?string $to,
        bool $finishedEvents
    ): PaginationInterface {
        $queryBuilder = $this->createQueryBuilderForViewPages($category, null);

        $queryBuilder
            ->innerJoin('e.participants', 'p')
            ->andWhere('p.id = '. $user->getId())
            ->addOrderBy('e.start', $finishedEvents ? 'DESC' : 'ASC');

        if ($finishedEvents) {
            $queryBuilder->andWhere('e.cancelled IS NULL');

            $reviewedEvents = $this->_em->createQueryBuilder()
                ->from('App:Event', 'event')
                ->join('event.reviews', 'reviews')
                ->select('event.id')
                ->andWhere('reviews.reviewer = '.$user->getId())
                ->getQuery()
                ->getArrayResult();

            if ($reviewedEvents) {
                $queryBuilder
                  ->andWhere('e.id NOT IN (:reviewedEvents)')
                  ->setParameter('reviewedEvents', array_column($reviewedEvents, 'id'), Connection::PARAM_INT_ARRAY);
            }
        }

        $now = new \DateTime();
        $fromDate = $from ? new \DateTime($from) : null;
        $toDate = $to ? new \DateTime($to) : null;
        if ($finishedEvents) {
            $toDate = ($toDate !== null && $toDate < $now) ? $toDate : $now;
        } else {
            $fromDate = ($fromDate !== null && $fromDate > $now) ? $fromDate : $now;
        }

        $this->setTimeSpanToQueryBuilder($fromDate, $toDate, $queryBuilder);

        return $this->paginator->paginate(
            $queryBuilder,
            $page,
            self::PUBLIC_VIEW_EVENT_LIST_LIMIT
        );
    }

    public function countUpcomingEventsForMember(User $member): int
    {
        $qb = $this->getCountEventsQueryBuilder($member);

        return (int)$qb
            ->andWhere('e.start > :now')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countPastEventsForMember(User $member): int
    {
        $qb = $this->getCountEventsQueryBuilder($member);

        return (int)$qb
            ->andWhere('e.start < :now')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function getCountEventsQueryBuilder(User $member): QueryBuilder
    {
        return $this->createQueryBuilder('e')
            ->select('count(e)')
            ->andWhere('e.published IS NOT null')
            ->andWhere('e.cancelled IS null')
            ->andWhere('e.organizer = '.$member->getId());
    }

    public function getParticipantNumberForEvents(array $eventIds): array
    {
        if (empty($eventIds)) {
            return [];
        }

        return $this->createQueryBuilder('e')
            ->andWhere('e.id IN (:eventIds)')
            ->setParameter('eventIds', $eventIds, Connection::PARAM_INT_ARRAY)
            ->groupBy('e.id')
            ->select('e.id, SIZE(e.participants) as participantsCount')
            ->getQuery()
            ->getArrayResult();
    }

    public function getUpdatesForUserEvents(array $eventIds, User $user): array
    {
        if (empty($eventIds)) {
            return [];
        }

        $result = $this->createQueryBuilder('e')
            ->select('e.id, e.lastVisitedAt, max(c.createdAt) as lastComment')
            ->join('e.comments', 'c')
            ->andWhere('e.id IN (:eventIds)')
            ->setParameter('eventIds', $eventIds, Connection::PARAM_INT_ARRAY)
            ->andWhere('e.organizer = '.$user->getId())
            ->andWhere('c.author != '.$user->getId())
            ->groupBy('e.id')
            ->getQuery()
            ->getArrayResult();

        $updates = [];
        foreach ($result as $eventWithComments) {
            $updates[$eventWithComments['id']] = $eventWithComments['lastVisitedAt'] < new \DateTime($eventWithComments['lastComment']);
        }

        return $updates;
    }

    public function countUpdatesForUserEvents(User $user): int
    {
        $result = $this->createQueryBuilder('e')
            ->select('e.id, e.lastVisitedAt, max(c.createdAt) as lastComment')
            ->join('e.comments', 'c')
            ->andWhere('e.published IS NOT NULL')
            ->andWhere('e.start > :now')
            ->setParameter('now', new \DateTime())
            ->andWhere('e.organizer = '.$user->getId())
            ->andWhere('c.author != '.$user->getId())
            ->groupBy('e.id')
            ->getQuery()
            ->getArrayResult();

        $updates = 0;
        foreach ($result as $event) {
            if ($event['lastVisitedAt'] < new \DateTime($event['lastComment'])) {
                $updates++;
            }
        }

        return $updates;
    }

    public function getEventParticipantImages(Event $event): array
    {
       return $this->createQueryBuilder('e')
            ->select('p.profileImage, p.slug, p.name')
            ->join('e.participants', 'p')
            ->andWhere('e.id = '.$event->getId())
            ->getQuery()
            ->getArrayResult();
    }

    /** @return Event[] */
    public function getMoreEventsFromSameAuthor(Event $event): array
    {
        return $this->createQueryBuilder('e')
            ->select('e')
            ->andWhere('e.organizer = :organizer')
            ->setParameter('organizer', $event->getOrganizer())
            ->andWhere('e.id != :id')
            ->setParameter('id', $event->getId())
            ->andWhere('e.published IS NOT null')
            ->andWhere('e.cancelled IS null')
            ->andWhere('e.start > :now')
            ->setParameter('now', new \DateTime())
            ->addOrderBy('e.start', 'ASC')
            ->setMaxResults(self::MORE_EVENTS_FROM_AUTHOR_LIMIT)
            ->getQuery()
            ->getResult();
    }

    public function isParticipant(User $user, Event $event): bool
    {
        $participant = $this->createQueryBuilder('e')
            ->leftJoin('e.participants', 'p')
            ->addselect('p')
            ->andWhere('p.id =' . $user->getId())
            ->andWhere('e.id = ' .  $event->getId())
            ->getQuery()
            ->getOneOrNullResult();

        return $participant ? true : false;
    }

    public function setAllUserEventsAsCanceled(User $user): void
    {
        $this->createQueryBuilder('e')
            ->update()
            ->set('e.cancelled', ':date')
            ->setParameter('date', new \DateTime())
            ->andWhere('e.organizer = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }

    private function createQueryBuilderForViewPages(int $category, ?User $user): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('e')->select('e');

        if ($category > 0 && $category < 5) {
            $queryBuilder->andWhere('e.category = '.$category);
        }

        if ($user) {
            $queryBuilder->andWhere('e.organizer = '.$user->getId());
        }

        return $queryBuilder;
    }

    private function setTimeSpanToQueryBuilder(?\DateTime $fromDate, ?\DateTime $toDate, QueryBuilder $queryBuilder): void
    {
        $fromDate ? $fromDate->setTime(0, 0, 0) : null;
        $toDate ? $toDate->setTime(24, 59, 59) : null;

        if ($fromDate) {
            $queryBuilder->andWhere('e.start > :from')
                ->setParameter('from', $fromDate);
        }

        if ($toDate && $toDate > $fromDate) {
            $queryBuilder->andWhere('e.start < :to')
                ->setParameter('to', $toDate);
        }
    }
}
