<?php
declare(strict_types=1);

namespace App\Model\Cockpit;

use App\Services\UploadHelper;

class MessageWrapper
{
    private $content;
    private $senderId;
    private $senderImage;
    private $createdAt;
    private $senderName;
    private $senderSlug;
    private ?string $image;

    public function __construct(
        string $content,
        int $senderId,
        ?string $senderImage,
        \DateTime $createdAt,
        string $senderName,
        string $senderSlug,
        ?string $image
    ) {
        $this->content = $content;
        $this->senderId = $senderId;
        $this->senderImage = $senderImage;
        $this->createdAt = $createdAt;
        $this->senderName = $senderName;
        $this->senderSlug = $senderSlug;
        $this->image = $image;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getSenderId(): int
    {
        return $this->senderId;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function getSenderImage(): string
    {
        return empty($this->senderImage) ? UploadHelper::USER_DEFAULT_IMAGE_SMALL_PATH :
            UploadHelper::USER_IMAGE_DIRECTORY_SMALL.'/'.$this->senderImage;
    }

    public function getSenderName(): string
    {
        return $this->senderName;
    }

    public function getSenderSlug(): string
    {
        return $this->senderSlug;
    }

    public function getImage(): string
    {
        return $this->image ? UploadHelper::FORUM_IMAGE_DIRECTORY.'/'.$this->image : '';
    }
}
