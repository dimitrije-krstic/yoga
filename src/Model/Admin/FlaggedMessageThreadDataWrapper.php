<?php
declare(strict_types=1);

namespace App\Model\Admin;

class FlaggedMessageThreadDataWrapper
{
    public const STATUS_NAME = [
        1 => 'flagged',
        2 => 'false claim',
        3 => 'fixed'
    ];

    private $reportId;
    private $threadId;
    private $authorSlug;
    private $authorEmail;
    private $flagCreatedAt;
    private $flagUpdatedAt;
    private $flagStatus;
    private $flagReason;
    private $reportingMemberEmail;

    public function __construct(
        int $reportId,
        int $threadId,
        string $authorSlug,
        string $authorEmail,
        \DateTime $flagCreatedAt,
        \DateTime $flagUpdatedAt,
        int $flagStatus,
        string $flagReason,
        string $reportingMemberEmail
    ) {
        $this->reportId = $reportId;
        $this->threadId = $threadId;
        $this->authorSlug = $authorSlug;
        $this->authorEmail = $authorEmail;
        $this->flagCreatedAt = $flagCreatedAt;
        $this->flagUpdatedAt = $flagUpdatedAt;
        $this->flagStatus = $flagStatus;
        $this->flagReason = $flagReason;
        $this->reportingMemberEmail = $reportingMemberEmail;
    }

    public function getReportId(): int
    {
        return $this->reportId;
    }

    public function getThreadId(): int
    {
        return $this->threadId;
    }

    public function getAuthorSlug(): string
    {
        return $this->authorSlug;
    }

    public function getAuthorEmail(): string
    {
        return $this->authorEmail;
    }

    public function getFlagCreatedAt(): \DateTime
    {
        return $this->flagCreatedAt;
    }

    public function getFlagUpdatedAt(): \DateTime
    {
        return $this->flagUpdatedAt;
    }

    public function getFlagStatus(): int
    {
        return $this->flagStatus;
    }

    public function getFlagStatusName(): string
    {
        return self::STATUS_NAME[$this->flagStatus];
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
