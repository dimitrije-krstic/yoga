<?php
declare(strict_types=1);

namespace App\Repository;

use App\Entity\PostComment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method PostComment|null find($id, $lockMode = null, $lockVersion = null)
 * @method PostComment|null findOneBy(array $criteria, array $orderBy = null)
 * @method PostComment[]    findAll()
 * @method PostComment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PostCommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PostComment::class);
    }

    public function getCommentInfoForPost(int $postId): array
    {
        return $this->createQueryBuilder('c')
            ->select('u.name, u.slug, u.profileImage as image, c.content, c.createdAt as created')
            ->leftJoin('c.author', 'u')
            ->leftJoin('c.post', 'p')
            ->andWhere('p.id = :postId')
            ->setParameter('postId', $postId)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getArrayResult();
    }
}
