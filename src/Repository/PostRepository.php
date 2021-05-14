<?php
declare(strict_types=1);

namespace App\Repository;

use App\Entity\Post;
use App\Entity\Tag;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;

/**
 * @method Post|null find($id, $lockMode = null, $lockVersion = null)
 * @method Post|null findOneBy(array $criteria, array $orderBy = null)
 * @method Post[]    findAll()
 * @method Post[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PostRepository extends ServiceEntityRepository
{
    public const COCKPIT_EDIT_POST_LIST_LIMIT = 12;
    public const PUBLIC_VIEW_POST_LIST_LIMIT = 12;
    public const MORE_POSTS_FROM_AUTHOR_LIMIT = 6;

    private $paginator;

    public function __construct(ManagerRegistry $registry, PaginatorInterface $paginator)
    {
        parent::__construct($registry, Post::class);
        $this->paginator = $paginator;
    }

    /** @return Post[] */
    public function getMorePostsFromSameAuthor(Post $post): array
    {
        return $this->createQueryBuilder('p')
            ->select('p')
            ->andWhere('p.author = :author')
            ->setParameter('author', $post->getAuthor())
            ->andWhere('p.id != :id')
            ->setParameter('id', $post->getId())
            ->andWhere('p.publishedAt IS NOT null')
            ->addOrderBy('p.publishedAt', 'DESC')
            ->setMaxResults(self::MORE_POSTS_FROM_AUTHOR_LIMIT)
            ->getQuery()
            ->getResult();
    }

    public function countPublishedPostsForMember(User $member): int
    {
        return (int)$this->createQueryBuilder('p')
            ->select('COUNT(p)')
            ->andWhere('p.author = :author')
            ->setParameter('author', $member)
            ->andWhere('p.publishedAt IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getOwnPostsPaginated(User $user, int $page, string $category, ?string $queryTerm = null): PaginationInterface
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->select('p')
            ->andWhere('p.author = :author')
            ->setParameter('author', $user)
            ->addOrderBy('p.createdAt', 'DESC')
            ->addOrderBy('p.id', 'DESC');

        if ($category && isset(array_flip(Post::CATEGORY)[$category])) {
            $categoryId = array_flip(Post::CATEGORY)[$category];
            $queryBuilder->andWhere('p.category = '. $categoryId );
        }

        if ($queryTerm) {
            $terms = array_filter(explode(' ', $queryTerm));

            $queryParts = [];
            foreach ($terms as $key => $term) {
                $queryParts[] = ' p.title LIKE :term'.$key;
            }
            $query = implode(' OR ', $queryParts);

            $queryBuilder->andWhere($query);
            foreach ($terms as $key => $term) {
                $queryBuilder->setParameter('term'.$key, '%' .$term. '%');
            }
        }

        return $this->paginator->paginate(
            $queryBuilder,
            $page,
            self::COCKPIT_EDIT_POST_LIST_LIMIT
        );
    }

    public function getLatestPublishedPosts(
        int $page,
        int $limit = self::PUBLIC_VIEW_POST_LIST_LIMIT,
        ?string $queryTerm = null
    ): PaginationInterface {
        $queryBuilder = $this->createQueryBuilderForViewPages($queryTerm);

        return $this->paginator->paginate(
            $queryBuilder,
            $page,
            $limit
        );
    }

    public function getPaginatedPostsForCategory(string $category, int $page, ?string $queryTerm): PaginationInterface
    {
        $queryBuilder = $this->createQueryBuilderForViewPages($queryTerm, true, $category);

        return $this->paginator->paginate(
            $queryBuilder,
            $page,
            self::PUBLIC_VIEW_POST_LIST_LIMIT
        );
    }

    public function getPaginatedPostsForAuthor(User $user, int $page, ?string $queryTerm, string $category): PaginationInterface
    {
        $queryBuilder = $this->createQueryBuilderForViewPages($queryTerm, true, $category);
        $queryBuilder->andWhere('p.author = :author')
            ->setParameter('author', $user);

        return $this->paginator->paginate(
            $queryBuilder,
            $page,
            self::PUBLIC_VIEW_POST_LIST_LIMIT
        );
    }

    public function getPaginatedFavoritePosts(User $user, int $page, string $category, ?string $queryTerm): PaginationInterface
    {
        $queryBuilder = $this->createQueryBuilderForViewPages($queryTerm, true, $category);
        $queryBuilder->leftJoin('p.favoriteBy', 'f')
            ->andWhere('f.id = :userId')
            ->setParameter('userId', $user->getId());

        return $this->paginator->paginate(
            $queryBuilder,
            $page,
            self::PUBLIC_VIEW_POST_LIST_LIMIT
        );
    }

    public function isPostLikedByUser(?User $user, Post $post): bool
    {
        if ($user === null) {
            return false;
        }

        $result = $this->createQueryBuilder('p')
            ->select('p.id')
            ->leftJoin('p.likedBy', 'l')
            ->andWhere('p.id = :postId')
            ->setParameter('postId', $post->getId())
            ->andWhere('l.id = :userId')
            ->setParameter('userId', $user->getId())
            ->getQuery()
            ->getOneOrNullResult();

        return $result ? true : false;
    }

    public function isPostFavoriteByUser(?User $user, Post $post): bool
    {
        if ($user === null) {
            return false;
        }

        $result = $this->createQueryBuilder('p')
            ->select('p.id')
            ->leftJoin('p.favoriteBy', 'f')
            ->andWhere('p.id = :postId')
            ->setParameter('postId', $post->getId())
            ->andWhere('f.id = :userId')
            ->setParameter('userId', $user->getId())
            ->getQuery()
            ->getOneOrNullResult();

        return $result ? true : false;
    }

    public function getLikeNumberForPosts(array $postIds): array
    {
        if (empty($postIds)) {
            return [];
        }

        return $this->createQueryBuilder('p')
            ->andWhere('p.id IN (:postIds)')
            ->setParameter('postIds', $postIds, Connection::PARAM_INT_ARRAY)
            ->groupBy('p.id')
            ->select('p.id, SIZE(p.likedBy) as likes')
            ->getQuery()
            ->getArrayResult();
    }

    public function getCommentNumberForPosts(array $postIds): array
    {
        if (empty($postIds)) {
            return [];
        }

        return $this->createQueryBuilder('p')
            ->andWhere('p.id IN (:postIds)')
            ->setParameter('postIds', $postIds, Connection::PARAM_INT_ARRAY)
            ->groupBy('p.id')
            ->select('p.id, SIZE(p.comments) as comments')
            ->getQuery()
            ->getArrayResult();
    }

    public function yieldAllPostsForTag(Tag $tag): \Generator
    {
        // DOCTRINE DOESN'T ALLOW GENERATOR WHEN USING JOIN
        $ids = $this->createQueryBuilder('p')
            ->select('p.id')
            ->leftJoin('p.tags', 't')
            ->andWhere('t.id = :tagId')
            ->setParameter('tagId', $tag->getId())
            ->getQuery()
            ->getArrayResult()
        ;

        if (empty($ids = array_column($ids, 'id'))) {
            return [];
        }

        $query = $this->createQueryBuilder('p')
            ->select('p')
            ->andWhere('p.id IN (:ids)')
            ->setParameter('ids', $ids, Connection::PARAM_INT_ARRAY)
            ->getQuery();

        foreach ($query->iterate() as $result) {
            yield $result[0];
        }
    }

    private function createQueryBuilderForViewPages(?string $queryTerm, bool $searchTags = false, string $category = ''): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->select('p')
            ->andWhere('p.publishedAt IS NOT null')
            ->addOrderBy('p.publishedAt', 'DESC');

        if ($category && isset(array_flip(Post::CATEGORY)[$category])) {
            $categoryId = array_flip(Post::CATEGORY)[$category];
            $queryBuilder->andWhere('p.category = '. $categoryId );
        }

        if ($queryTerm) {
            if ($searchTags) {
                $queryBuilder->leftJoin('p.tags', 't');
            }

            $terms = array_filter(explode(' ', $queryTerm));

            $queryParts = [];
            foreach ($terms as $key => $term) {
                $queryParts[] = ' p.title LIKE :term'. $key . ($searchTags ? ' OR t.slug LIKE :term'.$key.' ' : ' ');
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
