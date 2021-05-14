<?php
declare(strict_types=1);

namespace App\Services;

use App\Entity\Post;
use App\Entity\Tag;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class TagService
{
    private $postRepository;
    private $entityManager;
    private $logger;

    public function __construct(
        PostRepository $postRepository,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    )
    {
        $this->postRepository = $postRepository;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    public function reassignTags(Tag $tagToReassignTo, Tag $tagToDelete): bool
    {
        $this->entityManager->beginTransaction();
        try {
            /** @var Post $post */
            foreach ($this->postRepository->yieldAllPostsForTag($tagToDelete) as $post) {
                $post->addTag($tagToReassignTo);
                $this->entityManager->flush();
                $this->entityManager->clear(Post::class);
            }

            $this->entityManager->remove($tagToDelete);
            $this->entityManager->flush();
        } catch (\Exception $exception) {
            $this->entityManager->rollback();
            $this->logger->error('ERROR TagNameChange: '. $exception->getMessage());
            return false;
        }

        $this->entityManager->commit();

        return true;
    }
}
