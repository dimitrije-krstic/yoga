<?php
declare(strict_types=1);

namespace App\Repository;

use App\Entity\Tag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;

/**
 * @method Tag|null find($id, $lockMode = null, $lockVersion = null)
 * @method Tag|null findOneBy(array $criteria, array $orderBy = null)
 * @method Tag[]    findAll()
 * @method Tag[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TagRepository extends ServiceEntityRepository
{
    private const COCKPIT_EDIT_POST_TAG_LIST_LIMIT = 30;

    private $paginator;

    public function __construct(ManagerRegistry $registry, PaginatorInterface $paginator)
    {
        parent::__construct($registry, Tag::class);
        $this->paginator = $paginator;
    }

    public function getPaginatedTags(int $page, ?string $queryTerm = null): PaginationInterface
    {
        $queryBuilder = $this->createQueryBuilder('t')
            ->select('t')
            ->addOrderBy('t.slug', 'ASC');

        if ($queryTerm) {
            $queryBuilder->andWhere('t.slug LIKE :term')
                ->setParameter('term', '%' . $queryTerm. '%');
        }

        return $this->paginator->paginate(
            $queryBuilder,
            $page,
            self::COCKPIT_EDIT_POST_TAG_LIST_LIMIT
        );
    }
}
