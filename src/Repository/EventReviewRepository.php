<?php
declare(strict_types=1);

namespace App\Repository;

use App\Entity\Event;
use App\Entity\EventReview;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;

/**
 * @method EventReview|null find($id, $lockMode = null, $lockVersion = null)
 * @method EventReview|null findOneBy(array $criteria, array $orderBy = null)
 * @method EventReview[]    findAll()
 * @method EventReview[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EventReviewRepository extends ServiceEntityRepository
{
    public const ORGANIZER_REVIEWS_OVERVIEW = 4;
    private const ORGANIZER_REVIEWS_PER_PAGE = 24;

    private PaginatorInterface $paginator;

    public function __construct(ManagerRegistry $registry, PaginatorInterface $paginator)
    {
        parent::__construct($registry, EventReview::class);
        $this->paginator = $paginator;
    }

    //TODO implement later
    public function getOrganizerAverageRating(User $organizer): float
    {
        $result = $this->createQueryBuilder('er')
            ->select('AVG(er.grade)')
            ->leftJoin('er.event','e')
            ->leftJoin('e.organizer', 'o')
            ->andWhere('o.id = :id')
            ->setParameter('id', $organizer->getId())
            ->andWhere('er.createdAt > :pastDate')
            ->setParameter('pastDate', new \DateTime('6 months ago'))
            ->getQuery()
            ->getSingleScalarResult();

        return round($result, 1);
    }

    /**
     * @return EventReview[]
     */
    public function getLatestReviewsForOrganizer(User $organizer): array
    {
        return $this->createQueryBuilder('er')
            ->select('er')
            ->leftJoin('er.event','e')
            ->leftJoin('e.organizer', 'o')
            ->andWhere('o.id = :id')
            ->setParameter('id', $organizer->getId())
            ->orderBy('er.createdAt', 'DESC')
            ->setMaxResults(self::ORGANIZER_REVIEWS_OVERVIEW)
            ->getQuery()
            ->getResult();
    }

    public function getPaginatedReviewsForOrganizer(User $member, int $page): PaginationInterface
    {
        $queryBuilder = $this->createQueryBuilder('er')
            ->select('er')
            ->leftJoin('er.event', 'e')
            ->andWhere('e.organizer = '. $member->getId())
            ->orderBy('er.createdAt', 'DESC');

        return $this->paginator->paginate(
            $queryBuilder,
            $page,
            self::ORGANIZER_REVIEWS_PER_PAGE
        );
    }

    /**
     * @return EventReview[]
     */
    public function getReviewsForEvent(Event $event): array
    {
        return $this->createQueryBuilder('er')
            ->select('er')
            ->andWhere('er.event = '. $event->getId())
            ->orderBy('er.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function isGradedBy(User $user, Event $event): bool
    {
        $reviewer = $this->createQueryBuilder('er')
            ->select('er')
            ->leftJoin('er.event','e')
            ->andWhere('e.id = :id')
            ->setParameter('id', $event->getId())
            ->andWhere('er.reviewer = '.$user->getId())
            ->getQuery()
            ->getOneOrNullResult();

        return $reviewer ? true : false;
    }
}

