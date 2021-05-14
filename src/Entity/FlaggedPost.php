<?php
declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\FlaggedPostRepository")
 *
 * @ORM\Table(uniqueConstraints={
 *     @ORM\UniqueConstraint(name="post_id_user_id_idx", columns={"post_id", "user_id"})
 * })
 */
class FlaggedPost
{
    private const STATUS_FLAGGED = 1;
    private const STATUS_FALSE_CLAIM = 2;
    private const STATUS_MARKED_INAPPROPRIATE = 3;
    private const STATUS_CONTENT_FIXED = 4;

    use TimestampableEntity;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Post")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     * @var Post
     */
    private $post;

    /**
     * User that flagged the post
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
        Post $post,
        User $user
    ) {
        $this->post = $post;
        $this->user = $user;
        $this->status = self::STATUS_FLAGGED;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getPost(): Post
    {
        return $this->post;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function setReason(string $reason): FlaggedPost
    {
        $this->reason = $reason;
        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatusFalseClaim(): self
    {
        $this->status = self::STATUS_FALSE_CLAIM;
        return $this;
    }

    public function setStatusMarkedInappropriate(): self
    {
        $this->status = self::STATUS_MARKED_INAPPROPRIATE;
        return $this;
    }

    public function setStatusResolved(): self
    {
        $this->status = self::STATUS_CONTENT_FIXED;
        return $this;
    }
}
