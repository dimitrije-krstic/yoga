<?php
declare(strict_types=1);

namespace App\Model;

use App\Services\UploadHelper;

class CommentDataWrapper
{
    /**
     * @var string
     */
    private $content;

    /**
     * @var \DateTime
     */
    private $createdAt;

    /**
     * @var string
     */
    private $authorName;

    /**
     * @var string|null
     */
    private $authorProfileImage;

    private string $authorSlug;

    public function __construct(
        string $comment,
        \DateTime $createdAt,
        string $authorName,
        ?string $authorProfileImage,
        string $authorSlug
    ) {
        $this->content = $comment;
        $this->createdAt = $createdAt;
        $this->authorName = $authorName;
        $this->authorProfileImage = $authorProfileImage;
        $this->authorSlug = $authorSlug;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function getAuthorName(): string
    {
        return $this->authorName;
    }

    public function getAuthorProfileImage(): string
    {
        return empty($this->authorProfileImage) ? UploadHelper::USER_DEFAULT_IMAGE_SMALL_PATH :
            UploadHelper::USER_IMAGE_DIRECTORY_SMALL.'/'.$this->authorProfileImage ;
    }

    public function getAuthorSlug(): string
    {
        return $this->authorSlug;
    }
}
