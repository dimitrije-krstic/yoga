<?php
declare(strict_types=1);

namespace App\Model;

use App\Entity\Post;

class PostTeaserDataWrapper
{
    /**
     * @var Post[]
     */
    private $posts;

    /**
     * @var string[]
     */
    private $postAuthorNames;

    /**
     * @var int[]
     */
    private $likedByCount;

    /**
     * @var int[]
     */
    private $commentCount;

    public function __construct(
        array $posts,
        array $postAuthorNames,
        array $likedByCount,
        array $commentCount
    ) {
        $this->posts = $posts;
        $this->postAuthorNames = $postAuthorNames;
        $this->likedByCount = $likedByCount;
        $this->commentCount = $commentCount;
    }

    /**
     * @return Post[]
     */
    public function getPosts(): array
    {
        return $this->posts;
    }

    public function getPostAuthorName(Post $post): string
    {
        return $this->postAuthorNames[$post->getId()] ?? '';
    }

    public function getLikedByCount(Post $post): int
    {
        return $this->likedByCount[$post->getId()] ?? 0;
    }

    public function getCommentCount(Post $post): int
    {
        return $this->commentCount[$post->getId()] ?? 0;
    }
}
