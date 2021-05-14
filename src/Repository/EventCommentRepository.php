<?php
declare(strict_types=1);

namespace App\Repository;

use App\Entity\EventComment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method EventComment|null find($id, $lockMode = null, $lockVersion = null)
 * @method EventComment|null findOneBy(array $criteria, array $orderBy = null)
 * @method EventComment[]    findAll()
 * @method EventComment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EventCommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventComment::class);
    }

    public function getCommentInfoForEvent(int $eventId): array
    {
        return $this->createQueryBuilder('c')
            ->select('a.name, a.slug, a.profileImage, c.content, c.createdAt')
            ->leftJoin('c.author', 'a')
            ->leftJoin('c.event', 'e')
            ->andWhere('e.id = ' . $eventId)
            ->orderBy('c.createdAt', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }
}
