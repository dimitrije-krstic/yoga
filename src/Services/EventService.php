<?php
declare(strict_types=1);

namespace App\Services;

use App\Entity\Event;
use App\Entity\User;
use App\Model\CommentDataWrapper;
use App\Model\EventTeaserDataWrapper;
use App\Repository\EventCommentRepository;
use App\Repository\EventRepository;
use App\Repository\EventTrackerRepository;
use App\Repository\UserRepository;

class EventService
{
    private UserRepository $userRepository;
    private EventRepository $eventRepository;
    private EventCommentRepository $eventCommentRepository;
    private EventTrackerRepository $eventTrackerRepository;

    public function __construct(
        UserRepository $userRepository,
        EventRepository $eventRepository,
        EventCommentRepository $eventCommentRepository,
        EventTrackerRepository $eventTrackerRepository
    ) {
        $this->userRepository = $userRepository;
        $this->eventRepository = $eventRepository;
        $this->eventCommentRepository = $eventCommentRepository;
        $this->eventTrackerRepository = $eventTrackerRepository;
    }

    /**
     * @var Event[] $events
     */
    public function getEventTeaserDataWrapper(array $events, ?User $user): EventTeaserDataWrapper
    {
        $eventIds = [];
        foreach ($events as $event) {
            $eventIds[] = $event->getId();
        }

        $authorImages = [];
        $authorSlugs = [];
        foreach ($this->userRepository->getAuthorImagesForEvents($eventIds) as $result) {
            $authorImages[$result['id']] = $user || (bool)$result['public'] ? $result['profileImage'] : '';
            $authorSlugs [$result['id']] = $user || (bool)$result['public'] ? $result['slug'] : '';
        }

        $participantCount = [];
        foreach ($this->eventRepository->getParticipantNumberForEvents($eventIds) as $result) {
            $participantCount[$result['id']] = (int)$result['participantsCount'];
        }

        return new EventTeaserDataWrapper(
            $events,
            $authorImages,
            $participantCount,
            $authorSlugs
        );
    }

    /**
     * @var Event[] $events
     */
    public function getUserEventTeaserDataWrapperWithTracking(array $events, User $user): EventTeaserDataWrapper
    {
        $eventIds = [];
        $authorImages = [];
        $authorSlugs = [];
        foreach ($events as $event) {
            $eventIds[] = $event->getId();
            $authorImages[$event->getId()] = $user->getProfileImage() ?? '';
            $authorSlugs[$event->getId()] = $user->getSlug();
        }

        $participantCount = [];
        foreach ($this->eventRepository->getParticipantNumberForEvents($eventIds) as $result) {
            $participantCount[$result['id']] = (int)$result['participantsCount'];
        }

        $updates = $this->eventRepository->getUpdatesForUserEvents($eventIds, $user);

        return new EventTeaserDataWrapper(
            $events,
            $authorImages,
            $participantCount,
            $authorSlugs,
            $updates
        );
    }

    /**
     * @var Event[] $events
     */
    public function getBookmarkedEventTeaserDataWrapperWithTracking(
        array $events,
        User $user,
        bool $finishedEvents
    ): EventTeaserDataWrapper {
        $eventIds = [];
        foreach ($events as $event) {
            $eventIds[] = $event->getId();
        }

        $authorImages = [];
        $authorSlugs = [];
        foreach ($this->userRepository->getAuthorImagesForEvents($eventIds) as $result) {
            $authorImages[$result['id']] = $result['profileImage'];
            $authorSlugs [$result['id']] = $result['slug'];
        }

        $participantCount = [];
        foreach ($this->eventRepository->getParticipantNumberForEvents($eventIds) as $result) {
            $participantCount[$result['id']] = (int)$result['participantsCount'];
        }

        $updates = [];
        if (!$finishedEvents) {
            $updates = $this->eventTrackerRepository->getUpdateFlagsForBookmarkedEvents($eventIds, $user);
        }

        return new EventTeaserDataWrapper(
            $events,
            $authorImages,
            $participantCount,
            $authorSlugs,
            $updates
        );
    }

    /**
     * @return CommentDataWrapper[]
     */
    public function getCommentDataWrappers(Event $event): array
    {
        $comments = [];
        foreach ($this->eventCommentRepository->getCommentInfoForEvent($event->getId()) as $result) {
            $comments[] = new CommentDataWrapper(
                $result['content'],
                $result['createdAt'],
                $result['name'],
                $result['profileImage'],
                $result['slug']
            );
        }

        return $comments;
    }
}
