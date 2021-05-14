<?php
declare(strict_types=1);

namespace App\Model\Cockpit;

use App\Services\UploadHelper;

class MessageThreadWrapper
{
    private $id;
    private $updatedAt;
    private $subject;
    private $memberSlug;
    private $memberName;
    private $memberImage;
    private $read;
    private $messageCount;
    /**
     * @var string[]
     */
    private array $extractedMessages;

    public function __construct(
        int $id,
        \DateTime $updatedAt,
        string $subject,
        string $memberSlug,
        string $memberName,
        ?string $memberImage,
        bool $read,
        int $messageCount,
        array $extractedMessages = []
    ) {
        $this->id = $id;
        $this->updatedAt = $updatedAt;
        $this->subject = $subject;
        $this->memberSlug = $memberSlug;
        $this->memberName = $memberName;
        $this->memberImage = $memberImage;
        $this->read = $read;
        $this->messageCount = $messageCount;
        $this->extractedMessages = $extractedMessages;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getMemberSlug(): string
    {
        return $this->memberSlug;
    }

    public function getMemberName(): string
    {
        return $this->memberName;
    }

    public function getMemberImage(): string
    {
        return empty($this->memberImage) ? UploadHelper::USER_DEFAULT_IMAGE_SMALL_PATH :
            UploadHelper::USER_IMAGE_DIRECTORY_SMALL.'/'.$this->memberImage ;
    }

    public function isRead(): bool
    {
        return $this->read;
    }

    public function getMessageCount(): int
    {
        return $this->messageCount;
    }

    /**
     * @return array|string[]
     */
    public function getExtractedMessages(): array
    {
        return $this->extractedMessages;
    }
}
