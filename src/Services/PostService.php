<?php
declare(strict_types=1);

namespace App\Services;

use App\Entity\Post;
use App\Entity\User;
use App\Model\CommentDataWrapper;
use App\Model\PostTeaserDataWrapper;
use App\Repository\PostCommentRepository;
use App\Repository\PostRepository;
use App\Repository\UserRepository;

class PostService
{
    private $postRepository;
    private $userRepository;
    private $postCommentRepository;

    public function __construct(
        PostRepository $postRepository,
        UserRepository $userRepository,
        PostCommentRepository $postCommentRepository
    ) {
        $this->postRepository = $postRepository;
        $this->userRepository = $userRepository;
        $this->postCommentRepository = $postCommentRepository;
    }

    public function getPostTeaserDataWrapper(array $posts, ?User $user): PostTeaserDataWrapper
    {
        $postIds = [];
        foreach ($posts as $post) {
            $postIds[] = $post->getId();
        }

        $authors = [];
        foreach ($this->userRepository->getAuthorNamesForPosts($postIds) as $result) {
            if ($result['webPostAuthorName']) {
                $authors[$result['id']] = $result['webPostAuthorName'];
                continue;
            }

            $authors[$result['id']] = $user || (bool)$result['public'] ? $result['name'] : '';
        }

        $likes = [];
        foreach ($this->postRepository->getLikeNumberForPosts($postIds) as $result) {
            $likes[$result['id']] = (int)$result['likes'];
        }

        $comments = [];
        foreach ($this->postRepository->getCommentNumberForPosts($postIds) as $result) {
            $comments[$result['id']] = (int)$result['comments'];
        }

        return new PostTeaserDataWrapper(
            $posts,
            $authors,
            $likes,
            $comments
        );
    }

    /**
     * @return CommentDataWrapper[]
     */
    public function getCommentDataWrappers(Post $post): array
    {
        $comments = [];
        foreach ($this->postCommentRepository->getCommentInfoForPost($post->getId()) as $result) {
            $comments[] = new CommentDataWrapper(
                $result['content'],
                $result['created'],
                $result['name'],
                $result['image'],
                $result['slug']
            );
        }

        return $comments;
    }

    public function getNumberOfComments(Post $post): int
    {
        return $this->postCommentRepository->count(['post' => $post]);
    }
}
