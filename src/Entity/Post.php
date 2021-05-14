<?php
declare(strict_types=1);

namespace App\Entity;

use App\Services\UploadHelper;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PostRepository")
 * @ORM\Table(indexes={
 *     @ORM\Index(name="idx_title", columns={"title"}),
 *     @ORM\Index(name="idx_published_at", columns={"published_at"}),
 *     @ORM\Index(name="idx_category", columns={"category"})
 * })
 */
class Post
{
    public const MAX_NUMBER_OF_IMAGES = 10;

    public const CATEGORY = [
        1 => 'Exercise & Health',
        2 => 'Meditation & Mind',
        3 => 'Chanting & Spirituality',
        4 => 'Food & Travel',
        5 => 'Motherhood & Kids',
        6 => 'My Yoga Story',
        7 => 'Various',
    ];

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
    private $title = '';

    /**
     * @ORM\Column(type="string", length=100, unique=true)
     * @Gedmo\Slug(fields={"title"})
     * @var string
     */
    private $slug;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @var string|null
     */
    private $content;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @var \DateTime|null
     */
    private $publishedAt;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="posts")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     * @var User
     */
    private $author;

    /**
     * @ORM\Column(type="json")
     * @var array
     */
    private $images = [];

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @var \DateTime|null
     */
    private $markedAsInappropriateAt;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string|null
     */
    private $youtubeVideoId;

    /**
     * TODO remove (use separate many-to-many table)
     *
     * @ORM\OneToMany(targetEntity="App\Entity\PostComment", mappedBy="post", fetch="EXTRA_LAZY")
     * @ORM\OrderBy({"createdAt" = "DESC"})
     * @var Collection|PostComment[]
     */
    private $comments;

    /**
     * TODO remove (use separate many-to-many table)
     *
     * @ORM\ManyToMany(targetEntity="App\Entity\Tag", fetch="EXTRA_LAZY", cascade={"persist"})
     * @ORM\JoinTable(name="post_tag",
     *      joinColumns={@ORM\JoinColumn(name="post_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="tag_id", referencedColumnName="id", onDelete="CASCADE")}
     *      )
     * @var Collection|Tag[]
     */
    private $tags;

    /**
     * TODO remove (use separate many-to-many table)
     *
     * @ORM\ManyToMany(targetEntity="App\Entity\User", mappedBy="likedPosts", fetch="EXTRA_LAZY")
     * @var Collection|User[]
     */
    private $likedBy;

    /**
     * TODO remove (use separate many-to-many table)
     *
     * @ORM\ManyToMany(targetEntity="App\Entity\User", mappedBy="favoritePosts", fetch="EXTRA_LAZY")
     * @var Collection|User[]
     */
    private $favoriteBy;

    /**
     * @ORM\Column(type="integer")
     * @var int
     */
    private $category = 7;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @var bool|null
     */
    private $webPost = false;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string|null
     */
    private $webPostAuthorName;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string|null
     */
    private $webPostAuthorLink;

    public function __construct(UserInterface $author)
    {
        if (!$author instanceof User) {
            throw new \Exception('Post author has to be User object');
        }

        $this->author = $author;
        $this->comments = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->likedBy = new ArrayCollection();
        $this->favoriteBy = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
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

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;
        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getPublishedAt(): ?\DateTime
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(?\DateTime $publishedAt): self
    {
        $this->publishedAt = $publishedAt;
        return $this;
    }

    public function getAuthor(): User
    {
        return $this->author;
    }

    /**
     * Contains image file names of all post related images
     *
     * @return string[]
     */
    public function getImages(): array
    {
        return array_values($this->images);
    }

    public function addImage(string $imageFilename): self
    {
        if (!in_array($imageFilename, $this->images, true)) {
            $this->images[] = $imageFilename;
        }

        return $this;
    }

    public function removeImage(string $imageFilename): void
    {
        if (($key = array_search($imageFilename, $this->images, true)) !== false) {
            unset($this->images[$key]);
        }

        $this->images = array_values($this->images);
    }

    public function getImagePath(string $imageName)
    {
        return UploadHelper::POST_IMAGE_DIRECTORY.'/'.$imageName;
    }

    public function getSmallImagePath(string $imageName)
    {
        return UploadHelper::POST_IMAGE_DIRECTORY_SMALL.'/'.$imageName;
    }

    /**
     * @return Collection|Tag[]
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(Tag $tag): self
    {
        if (!$this->tags->contains($tag)) {
            $this->tags[] = $tag;
        }
        return $this;
    }

    public function removeTag(Tag $tag): self
    {
        if ($this->tags->contains($tag)) {
            $this->tags->removeElement($tag);
        }
        return $this;
    }

    public function setTags(ArrayCollection $tags): self
    {
        $this->tags = $tags;
        return $this;
    }

    public function getMarkedAsInappropriateAt(): ?\DateTime
    {
        return $this->markedAsInappropriateAt;
    }

    public function setMarkedAsInappropriateAt(?\DateTime $markedAsInappropriateAt): self
    {
        $this->markedAsInappropriateAt = $markedAsInappropriateAt;
        return $this;
    }

    public function getYoutubeVideoId(): ?string
    {
        return $this->youtubeVideoId;
    }

    public function setYoutubeVideoId(?string $youtubeVideoId): self
    {
        $this->youtubeVideoId = $youtubeVideoId;
        return $this;
    }

    /**
     * @return Collection|User[]
     */
    public function getLikedBy(): Collection
    {
        return $this->likedBy;
    }

    public function getCategory(): int
    {
        return $this->category;
    }

    public function setCategory(int $category): self
    {
        $this->category = isset(self::CATEGORY[$category]) ? $category : 0;
        return $this;
    }

    public function isWebPost(): bool
    {
        return $this->webPost ?? false;
    }

    public function setWebPost(?bool $webPost): self
    {
        $this->webPost = $webPost;
        return $this;
    }

    public function getWebPostAuthorName(): string
    {
        return $this->webPostAuthorName ?? '';
    }

    public function setWebPostAuthorName(?string $webPostAuthorName): self
    {
        $this->webPostAuthorName = $webPostAuthorName;
        return $this;
    }

    public function getWebPostAuthorLink(): ?string
    {
        return $this->webPostAuthorLink;
    }

    public function setWebPostAuthorLink(?string $webPostAuthorLink): self
    {
        $this->webPostAuthorLink = $webPostAuthorLink;
        return $this;
    }
}