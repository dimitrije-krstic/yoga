<?php
declare(strict_types=1);

namespace App\Repository;

use App\Entity\VisitorsCounter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method VisitorsCounter|null find($id, $lockMode = null, $lockVersion = null)
 * @method VisitorsCounter|null findOneBy(array $criteria, array $orderBy = null)
 * @method VisitorsCounter[]    findAll()
 * @method VisitorsCounter[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VisitorsCounterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VisitorsCounter::class);
    }

    public function increase(string $route): void
    {
        $this->getEntityManager()
            ->createQuery(
                'UPDATE App\Entity\VisitorsCounter vc SET vc.count = vc.count + 1 WHERE vc.route = :route'
            )
            ->setParameter('route', $route)
            ->execute();
    }
}
