<?php
declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\MessageThreadRepository")
 * @ORM\Table(indexes={
 *     @ORM\Index(name="idx_subject", columns={"subject"}),
 *     @ORM\Index(name="idx_spam", columns={"spam"}),
 *     @ORM\Index(name="idx_admin", columns={"admin"}),
 *     @ORM\Index(name="idx_type", columns={"type"})
 * })
 */
class MessageThread
{
    public const TYPE_DIRECT_MESSAGES = 1;
    public const TYPE_NOTIFICATION = 2;
    public const TYPE_FORUM = 3;

    use TimestampableEntity;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @var string
     */
    private $subject = '';

    /**
     * @ORM\Column(type="boolean")
     * @var bool
     */
    private $spam = false;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="threads", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     * @var User
     */
    private $createdBy;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(nullable=true, onDelete="CASCADE")
     * @var User|null
     */
    private $receiver;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Message", mappedBy="thread", fetch="EXTRA_LAZY", cascade={"persist"})
     * @ORM\OrderBy({"createdAt" = "DESC"})
     * @var Collection|Message[]
     */
    private $messages;

    /**
     * @ORM\Column(type="boolean")
     * @var bool
     */
    private $admin;

    /**
     * @ORM\Column(type="smallint")
     * @var int
     */
    private $type;
    
    private function __construct(User $createdBy, ?User $receiver, bool $fromAdmin, int $type)
    {
        $this->createdBy = $createdBy;
        $this->receiver = $receiver;
        $this->messages = new ArrayCollection();
        $this->admin = $fromAdmin;
        $this->type = $type;
    }

    public static function createDirectMessageThread(User $createdBy, User $receiver, bool $fromAdmin): self
    {
        return new self($createdBy, $receiver, $fromAdmin, self::TYPE_DIRECT_MESSAGES);
    }

    public static function createNotificationThread(User $createdBy, bool $fromAdmin): self
    {
        return new self($createdBy, null, $fromAdmin, self::TYPE_NOTIFICATION);
    }

    public static function createForumThread(User $createdBy): self
    {
        return new self($createdBy, null, false, self::TYPE_FORUM);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function isSpam(): bool
    {
        return $this->spam;
    }

    public function getCreatedBy(): User
    {
        return $this->createdBy;
    }

    public function getReceiver(): User
    {
        return $this->receiver;
    }

    /**
     * @return Collection|Message[]
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function setSubject(string $subject): self
    {
        $this->subject = $subject;
        return $this;
    }
    
    public function markAsSpam(): self
    {
        $this->spam = true;
        return $this;
    }

    public function removeFromSpam(): self
    {
        $this->spam = false;
        return $this;
    }

    public function addMessage(Message $message): self
    {
        if (!$this->messages->contains($message)) {
            $this->messages[] = $message;
        }
        $message->setThread($this);

        return $this;
    }

    public function removeMessage(Message $message): self
    {
        if ($this->messages->contains($message)) {
            $this->messages->removeElement($message);
        }
        return $this;
    }

    public function isAdmin(): bool
    {
        return $this->admin;
    }

    public function getType(): int
    {
        return $this->type;
    }
}
