<?php
declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\EventCommentRepository")
 */
class EventComment
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
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     * @var User
     */
    private $author;

    /**
     * @ORM\Column(type="text")
     * @var string
     */
    private $content = '';

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Event", inversedBy="comments", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     * @var Event
     */
    private $event;

    public function __construct(User $author, Event $event)
    {
        $this->author = $author;
        $this->event = $event;
        $this->createdAt = new \DateTime();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getAuthor(): User
    {
        return $this->author;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getEvent(): Event
    {
        return $this->event;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }
}
