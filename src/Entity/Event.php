<?php
declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity(repositoryClass="App\Repository\EventRepository")
 * @ORM\Table(indexes={
 *     @ORM\Index(name="idx_category", columns={"category"}),
 *     @ORM\Index(name="idx_start", columns={"start"}),
 *     @ORM\Index(name="idx_published", columns={"published"}),
 *     @ORM\Index(name="idx_cancelled", columns={"cancelled"})
 * })
 */
class Event
{
    use TimestampableEntity;

    public const CATEGORY = [
        1 => 'asana',
        2 => 'meditation',
        3 => 'kirtan',
        4 => 'satsang',
        5 => 'other'
    ];

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
    private $title;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @var string|null
     */
    private $description;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string|null
     */
    private $link;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string|null
     */
    private $linkPassword;

    /**
     * @ORM\Column(type="integer")
     * @var int
     */
    private $category;

    /**
     * @ORM\Column(type="datetime")
     * @var \DateTime
     */
    private $start;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    private $timezone;

    /**
     * @ORM\Column(type="integer")
     * @var integer
     */
    private $duration;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="events", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     * @var User
     */
    private $organizer;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @var \DateTime|null
     */
    private $published;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @var \DateTime|null
     */
    private $cancelled;

    /**
     * Marks the time of the last visit of the event page by organizer
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @var \DateTime|null
     */
    private $lastVisitedAt;

    /**
     * TODO remove (use separate many-to-many table)
     *
     * @ORM\OneToMany(targetEntity="App\Entity\EventComment", mappedBy="event", fetch="EXTRA_LAZY", cascade={"persist"})
     * @ORM\OrderBy({"createdAt" = "ASC"})
     * @var Collection|EventComment[]
     */
    private $comments;

    /**
     * TODO remove (use separate many-to-many table)
     *
     * @ORM\ManyToMany(targetEntity="App\Entity\User", fetch="EXTRA_LAZY", cascade={"persist"})
     * @ORM\JoinTable(name="event_participants")
     * @var Collection|User[]
     */
    private $participants;

    /**
     * TODO remove (use separate many-to-many table)
     *
     * @ORM\OneToMany(targetEntity="App\Entity\EventReview", mappedBy="event", fetch="EXTRA_LAZY", cascade={"persist"})
     * @ORM\OrderBy({"createdAt" = "ASC"})
     * @var Collection|EventReview[]
     */
    private $reviews;

    public function __construct(UserInterface $organizer)
    {
        if (!$organizer instanceof User) {
            throw new \Exception('Event organizer has to be User object');
        }

        $this->organizer = $organizer;
        $this->comments = new ArrayCollection();
        $this->reviews = new ArrayCollection();
        $this->participants = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getDescriptionInParagraphs(): array
    {
        return $this->description? array_values(array_filter(array_map('trim', explode(PHP_EOL, $this->description)))) : [];
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getCategory(): int
    {
        return $this->category;
    }

    public function setCategory(int $category): self
    {
        $this->category = isset(self::CATEGORY[$category]) ? $category : 5;
        return $this;
    }

    public function getStart(): \DateTime
    {
        return $this->start;
    }

    public function setStart(\DateTime $start): self
    {
        $this->start = $start;
        return $this;
    }

    public function getTimezoneShort(): string
    {
        $dt = new \DateTime('now', new \DateTimeZone($this->timezone));
        return $dt->format('T');
    }

    public function getTimezone(): string
    {
        return $this->timezone;
    }

    public function setTimezone(string $timezone): self
    {
        $this->timezone = $timezone;
        return $this;
    }

    public function getDuration(): int
    {
        return $this->duration;
    }

    public function getFormatedDuration(): string
    {
        if (empty($this->duration)) {
            return '';
        }

        $hours = floor($this->duration / 60);
        $unit = (int)$hours === 1 ? 'hour' : 'hours';
        $min = $this->duration - ($hours * 60);

        return ($hours ? $hours .' '.$unit.' ' : '') . ($min ? $min .' min.' : '');
    }

    public function setDuration(int $duration): self
    {
        $this->duration = $duration;
        return $this;
    }

    public function getOrganizer(): User
    {
        return $this->organizer;
    }

    public function setOrganizer(User $organizer): self
    {
        $this->organizer = $organizer;
        return $this;
    }

    /**
     * @return EventComment[]|Collection
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(EventComment $comment): self
    {
        if (!$this->comments->contains($comment)) {
            $this->comments[] = $comment;
        }
        return $this;
    }

    /**
     * @return User[]|Collection
     */
    public function getParticipants(): Collection
    {
        return $this->participants;
    }

    public function addParticipant(User $user): self
    {
        if (!$this->participants->contains($user)) {
            $this->participants[] = $user;
        }
        return $this;
    }

    public function removeParticipant(User $user): self
    {
        if ($this->participants->contains($user)) {
            $this->participants->removeElement($user);
        }
        return $this;
    }

    /**
     * @return EventReview[]|Collection
     */
    public function getReviews(): Collection
    {
        return $this->reviews;
    }

    public function addReview(EventReview $review): self
    {
        if (!$this->reviews->contains($review)) {
            $this->reviews[] = $review;
        }
        return $this;
    }

    public function getPublished(): ?\DateTime
    {
        return $this->published;
    }

    public function publishEvent(): self
    {
        $this->published = new \DateTime();
        return $this;
    }

    public function setPublished(\DateTime $publishDate): self
    {
        $this->published = $publishDate;
        return $this;
    }

    public function getCancelled(): ?\DateTime
    {
        return $this->cancelled;
    }

    public function cancelEvent(): self
    {
        $this->cancelled = new \DateTime();
        return $this;
    }

    public function setCancelled(\DateTime $cancelDate): self
    {
        $this->cancelled = $cancelDate;
        return $this;
    }

    /**
     * Time when the organizer was last visiting the page
     */
    public function getLastVisitedAt(): ?\DateTime
    {
        return $this->lastVisitedAt;
    }

    public function setVisited(): self
    {
        $this->lastVisitedAt = new \DateTime();
        return $this;
    }

    public function getLink(): ?string
    {
        return $this->link;
    }

    public function setLink(?string $link): self
    {
        $this->link = $link;
        return $this;
    }

    public function getLinkPassword(): ?string
    {
        return $this->linkPassword;
    }

    public function setLinkPassword(?string $linkPassword): self
    {
        $this->linkPassword = $linkPassword;
        return $this;
    }
}
