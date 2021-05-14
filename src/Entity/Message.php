<?php
declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\MessageRepository")
 * @ORM\Table(indexes={
 *     @ORM\Index(name="idx_is_read", columns={"is_read"})
 * })
 */
class Message
{
    use TimestampableEntity;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\MessageThread", inversedBy="messages")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     * @var MessageThread
     */
    private $thread;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     * @var User
     */
    private $createdBy;

    /**
     * @ORM\Column(type="text")
     * @var string
     */
    private $content = '';

    /**
     * Used to track unread messaged made or received by User
     *
     * @ORM\Column(type="boolean", name="is_read")
     * @var bool
     */
    private $read = false;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var null|string
     */
    private $image;

    public function __construct(User $createdBy)
    {
        $this->createdBy = $createdBy;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getThread(): MessageThread
    {
        return $this->thread;
    }

    public function setThread(MessageThread $thread): self
    {
        $this->thread = $thread;
        return $this;
    }

    public function getCreatedBy(): User
    {
        return $this->createdBy;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function isRead(): bool
    {
        return $this->read;
    }

    public function markAsRead(): self
    {
        $this->read = true;
        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): self
    {
        $this->image = $image;
        return $this;
    }
}
