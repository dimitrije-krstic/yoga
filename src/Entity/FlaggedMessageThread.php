<?php
declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\FlaggedMessageThreadRepository")
 *
 * @ORM\Table(uniqueConstraints={
 *     @ORM\UniqueConstraint(name="thread_id_user_id_idx", columns={"thread_id", "user_id"})
 * })
 */
class FlaggedMessageThread
{
    private const STATUS_FLAGGED = 1;
    private const STATUS_FALSE_CLAIM = 2;
    private const STATUS_CONTENT_FIXED = 3;

    private const STATUSES = [
        self::STATUS_FLAGGED,
        self::STATUS_FALSE_CLAIM,
        self::STATUS_CONTENT_FIXED
    ];

    use TimestampableEntity;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\MessageThread")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     * @var MessageThread
     */
    private $thread;

    /**
     * User that flagged the thread
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @var User|null
     */
    private $user;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    private $reason = '';

    /**
     * @ORM\Column(type="smallint")
     * @var int
     */
    private $status;

    public function __construct(
        MessageThread $thread,
        User $user
    ) {
        $this->thread = $thread;
        $this->user = $user;
        $this->status = self::STATUS_FLAGGED;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getThread(): MessageThread
    {
        return $this->thread;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        if (in_array($status, self::STATUSES)) {
            $this->status = $status;
        }

        return $this;
    }

    public function setReason(string $reason): self
    {
        $this->reason = $reason;
        return $this;
    }
}
