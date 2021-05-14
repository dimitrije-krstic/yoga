<?php
declare(strict_types=1);

namespace App\Model;

use App\Entity\Event;
use App\Services\UploadHelper;

class EventTeaserDataWrapper
{
    /**
     * @var Event[]
     */
    private array $events;
    private array $eventAuthorImages;
    private array $participantCount;
    private array $authorSlugs;
    private array $updates;

    public function __construct(
        array $events,
        array $eventAuthorImages,
        array $participantCount,
        array $authorSlugs,
        array $updates = []
    ) {
        $this->events = $events;
        $this->eventAuthorImages = $eventAuthorImages;
        $this->participantCount = $participantCount;
        $this->authorSlugs = $authorSlugs;
        $this->updates = $updates;
    }

    /**
     * @return Event[]
     */
    public function getEvents(): array
    {
        return $this->events;
    }

    public function getEventAuthorImage(Event $event): string
    {
        $image = $this->eventAuthorImages[$event->getId()] ?? '';

        return $image ? UploadHelper::USER_IMAGE_DIRECTORY.'/'.$image
            : UploadHelper::USER_DEFAULT_IMAGE_PATH;
    }

    public function getAuthorSlug(Event $event): string
    {
        return $this->authorSlugs[$event->getId()] ?? '';
    }

    public function getParticipantCount(Event $event): int
    {
        return $this->participantCount[$event->getId()] ?? 0;
    }

    public function hasUpdates(Event $event): bool
    {
        return $this->updates[$event->getId()] ?? false;
    }
}
