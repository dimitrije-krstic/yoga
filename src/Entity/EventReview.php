<?php
declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\EventReviewRepository")
 */
class EventReview
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
    private $reviewer;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Event", inversedBy="reviews", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     * @var Event
     */
    private $event;

    /**
     * @ORM\Column(type="text")
     * @var string
     */
    private $text = '';

    /**
     * @ORM\Column(type="smallint")
     * @var int
     */
    private $grade = 5;

    public function __construct(User $reviewer, Event $event)
    {
        $this->createdAt = new \DateTime();
        $this->reviewer = $reviewer;
        $this->event = $event;
    }

    public function setText(string $text): self
    {
        $this->text = $text;
        return $this;
    }

    public function setGrade(int $grade): self
    {
        $this->grade = $grade;
        return $this;
    }

    public function setUpdatedAt(\DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getReviewer(): User
    {
        return $this->reviewer;
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getGrade(): int
    {
        return $this->grade;
    }

    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }
}
