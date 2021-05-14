<?php
declare(strict_types=1);

namespace App\Model\Admin;

class FlaggedPostDataWrapper
{
    public const STATUS_NAME = [
        1 => 'flagged',
        2 => 'false claim',
        3 => 'inappropriate',
        4 => 'fixed'
    ];

    private $postId;
    private $postSlug;
    private $authorSlug;
    private $authorEmail;
    private $reportId;
    private $flagCreatedAt;
    private $flagUpdatedAt;
    private $flagStatus;
    private $flagReason;
    private $reportingMemberEmail;

    public function __construct(
        int $postId,
        string $postSlug,
        string $authorSlug,
        string $authorEmail,
        int $reportId,
        \DateTime $flagCreatedAt,
        \DateTime $flagUpdatedAt,
        int $flagStatus,
        string $flagReason,
        string $reportingMemberEmail
    ) {
        $this->postId = $postId;
        $this->postSlug = $postSlug;
        $this->authorSlug = $authorSlug;
        $this->authorEmail = $authorEmail;
        $this->reportId = $reportId;
        $this->flagCreatedAt = $flagCreatedAt;
        $this->flagUpdatedAt = $flagUpdatedAt;
        $this->flagStatus = $flagStatus;
        $this->flagReason = $flagReason;
        $this->reportingMemberEmail = $reportingMemberEmail;
    }

    public function getPostId(): int
    {
        return $this->postId;
    }

    public function getPostSlug(): string
    {
        return $this->postSlug;
    }

    public function getAuthorSlug(): string
    {
        return $this->authorSlug;
    }

    public function getAuthorEmail(): string
    {
        return $this->authorEmail;
    }

    public function getReportId(): int
    {
        return $this->reportId;
    }

    public function getFlagCreatedAt(): \DateTime
    {
        return $this->flagCreatedAt;
    }

    public function getFlagUpdatedAt(): \DateTime
    {
        return $this->flagUpdatedAt;
    }

    public function getFlagStatus(): string
    {
        return self::STATUS_NAME[$this->flagStatus];
    }

    public function getFlagStatusId(): int
    {
        return $this->flagStatus;
    }

    public function getFlagReason(): string
    {
        return $this->flagReason;
    }

    public function getReportingMemberEmail(): string
    {
        return $this->reportingMemberEmail;
    }
}
