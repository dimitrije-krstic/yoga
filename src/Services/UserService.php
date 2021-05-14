<?php
declare(strict_types=1);

namespace App\Services;

use App\Entity\BlockedUser;
use App\Entity\Post;
use App\Entity\User;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;

class UserService
{
    private $entityManager;
    private $uploadHelper;
    private EventRepository $eventRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        UploadHelper $uploadHelper,
        EventRepository $eventRepository
    ) {
        $this->entityManager = $entityManager;
        $this->uploadHelper = $uploadHelper;
        $this->eventRepository = $eventRepository;
    }

    /**
     * User account is transformed to anonymous user account
     * 1. all user personal information will be deleted
     * 2. all user contributions are kept under anonymous account
     * 3. all user upcoming events are cancelled
     */
    public function softDeleteUserProfile(User $user): void
    {
        $this->entityManager->remove(
            $user->getUserInfo()
        );
        $user->deleteAssociatedUserInfo();
        $this->entityManager->flush();

        // delete profile image
        if ($profileImage = $user->getProfileImage()) {
            $this->uploadHelper->deleteUserProfileImage($profileImage);
            $user->setProfileImage(null);
        }

        $user->setEmail(uniqid().'_del_'.$user->getEmail())
            ->setName('member')
            ->setCurrentLocation(null)
            ->setAccountPubliclyVisible(false)
            ->setGoogleId(null)
            ->setFacebookId(null)
            ->setVerified(false)
            ->setTimezone(null)
            ->setDeletedAt(new \DateTime());

        $this->entityManager->flush();

        $this->eventRepository->setAllUserEventsAsCanceled($user);
    }

    /**
     * All user personal information and contributions will be deleted
     * user email will be stored in BlockList preventing same user to register again
     */
    public function blockUser(User $userToBlock, User $admin, string $reason): void
    {
        $this->deleteUserCreatedMediaContent($userToBlock);
        $this->entityManager->remove($userToBlock);

        $blocklistedUserRecord = new BlockedUser(
            $userToBlock->getEmail(),
            $userToBlock->getName(),
            $userToBlock->getCreatedAt(),
            $admin->getName(),
            $reason
        );

        $this->entityManager->persist($blocklistedUserRecord);
        $this->entityManager->flush();
    }

    /**
     * TODO later move this to background cron job
     */
    private function deleteUserCreatedMediaContent(User $user): void
    {
        // delete profile images
        if ($profileImage = $user->getProfileImage()) {
            try {
                $this->uploadHelper->deleteUserProfileImage($profileImage);
            } catch (\Exception $e) {}
        }

        // delete post images
        /** @var Post $post */
        // TODO REMOVE getPosts and use yield from PostRepo
        foreach ($user->getPosts() as $post) {
            if ($images = $post->getImages()) {
                foreach ($images as $image) {
                    try {
                        $this->uploadHelper->deletePostImage($image);
                    } catch (\Exception $e) {}

                }
            }
        }
    }
}
