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
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @ORM\Table(indexes={
 *     @ORM\Index(name="idx_slug", columns={"slug"}),
 *     @ORM\Index(name="idx_name", columns={"name"}),
 *     @ORM\Index(name="idx_accepted_terms_of_use", columns={"accepted_terms_of_use"}),
 *     @ORM\Index(name="idx_deleted_at", columns={"deleted_at"}),
 *     @ORM\Index(name="idx_verified", columns={"verified"}),
 *     @ORM\Index(name="idx_account_publicly_visible", columns={"account_publicly_visible"}),
 *     @ORM\Index(name="idx_current_location", columns={"current_location"})
 * })
 */
class User implements UserInterface
{
    use TimestampableEntity;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\UserInfo", fetch="EXTRA_LAZY", cascade={"persist", "remove"})
     * @var UserInfo|null
     */
    private $userInfo;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     * @var string
     */
    private $email;

    /**
     * @ORM\Column(type="string", unique=true)
     * @Gedmo\Slug(fields={"name"})
     * @var string
     */
    private $slug;

    /**
     * @ORM\Column(type="string", length=255)
     * @var string
     */
    private $name;

    /**
     * @ORM\Column(type="json")
     * @var array
     */
    private $roles = [];

    /**
     * Hashed password
     *
     * @ORM\Column(type="string")
     * @var string
     */
    private $password = '';

    /**
     * Required on user registration
     * Admin Users are created separately and have a false values (this is used as a flag)
     *
     * @ORM\Column(type="boolean")
     * @var boolean
     */
    private $acceptedTermsOfUse;

    /**
     * User can delete own account (cannot be deleted by admin directly)
     * Private data are deleted, email is obfuscated, and all activities preserved anonymously
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @var \DateTime|null
     */
    private $deletedAt;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string|null
     */
    private $profileImage;

    /**
     * User needs to verify his email address,
     * or login through social-network-sso
     *
     * @ORM\Column(type="boolean")
     * @var bool
     */
    private $verified = false;

    /**
     * @ORM\Column(type="boolean")
     * @var bool
     */
    private $accountPubliclyVisible = true;

    /**
     * @ORM\Column(type="string", nullable=true, length=255)
     * @var string|null
     */
    private $currentLocation;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string|null
     */
    private $timezone;

    /**
     * TODO remove (make unidirectional relation from posts)
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Post", mappedBy="author", fetch="EXTRA_LAZY")
     * @ORM\OrderBy({"createdAt" = "DESC"})
     * @var Collection|Post[]
     */
    private $posts;

    /**
     * TODO remove (make unidirectional relation from posts)
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Event", mappedBy="organizer", fetch="EXTRA_LAZY")
     * @ORM\OrderBy({"start" = "DESC"})
     * @var Collection|Event[]
     */
    private $events;

    /**
     * Members that follow User (cannot be added or delete by User)
     * TODO remove (use separate many-to-many table)
     *
     * @ORM\ManyToMany(targetEntity="App\Entity\User", mappedBy="following", fetch="EXTRA_LAZY")
     * @var Collection|User[]
     */
    private $followedBy;

    /**
     * Members that the User follows
     * TODO remove (use separate many-to-many table)
     *
     * @ORM\ManyToMany(targetEntity="App\Entity\User", inversedBy="followedBy", fetch="EXTRA_LAZY")
     *
     * @ORM\JoinTable(name="user_followers",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="followed_user_id", referencedColumnName="id")}
     *      )
     *
     * @var Collection|User[]
     */
    private $following;

    /**
     * TODO remove (use separate many-to-many table)
     *
     * @ORM\ManyToMany(targetEntity="App\Entity\Post", inversedBy="likedBy", fetch="EXTRA_LAZY")
     * @ORM\JoinTable(name="post_likes")
     * @var Collection|Post[]
     */
    private $likedPosts;

    /**
     * TODO remove (use separate many-to-many table)
     *
     * @ORM\ManyToMany(targetEntity="App\Entity\Post", inversedBy="favoriteBy", fetch="EXTRA_LAZY")
     * @ORM\JoinTable(name="post_favorites")
     * @var Collection|Post[]
     */
    private $favoritePosts;

    /**
     * Message threads initiated by User
     * TODO remove (use separate many-to-many table)
     *
     * @ORM\OneToMany(targetEntity="App\Entity\MessageThread", mappedBy="createdBy", fetch="EXTRA_LAZY")
     * @var Collection|MessageThread[]
     */
    private $threads;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string|null
     */
    private $googleId;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string|null
     */
    private $facebookId;

    /**
     * @ORM\Column(type="boolean")
     * @var bool
     */
    private $isPatron = false;

    public function __construct(
        string $email,
        string $name,
        bool $acceptedTermsOfUse,
        array $roles = []
    ) {
        $this->email = $email;
        $this->name = $name;
        $this->acceptedTermsOfUse = $acceptedTermsOfUse;
        $this->roles = $roles;
        $this->userInfo = new UserInfo();

        $this->posts = new ArrayCollection();
        $this->followedBy = new ArrayCollection();
        $this->following = new ArrayCollection();
        $this->likedPosts = new ArrayCollection();
        $this->favoritePosts = new ArrayCollection();
        $this->threads = new ArrayCollection();
        $this->events = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserInfo(): UserInfo
    {
        return $this->userInfo;
    }

    public function deleteAssociatedUserInfo(): self
    {
        $this->userInfo = new UserInfo();
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
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

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUsername(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;
        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getPassword(): string
    {
        return (string) $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function isAcceptedTermsOfUse(): bool
    {
        return $this->acceptedTermsOfUse ?? false;
    }

    public function setAcceptedTermsOfUse(bool $acceptedTermsOfUse): self
    {
        $this->acceptedTermsOfUse = $acceptedTermsOfUse;
        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getSalt()
    {
        // not needed when using the "bcrypt" algorithm in security.yaml
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getDeletedAt(): ?\DateTime
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTime $deletedAt): self
    {
        $this->deletedAt = $deletedAt;
        return $this;
    }

    /**
     * @return Collection|Post[]
     * @deprecated use repo to fetch paginatedPosts or yield large collection
     */
    public function getPosts(): Collection
    {
        return $this->posts;
    }

    public function getProfileImage(): ?string
    {
        return $this->profileImage;
    }

    public function setProfileImage(?string $profileImage): self
    {
        $this->profileImage = $profileImage;
        return $this;
    }

    /**
     * Used in Twig to render Profile image
     */
    public function getProfileImagePath(): string
    {
        // TODO implement Amazon S3 storage
        return $this->profileImage === null ?  UploadHelper::USER_DEFAULT_IMAGE_PATH :
            UploadHelper::USER_IMAGE_DIRECTORY.'/'.$this->profileImage;
    }

    public function isVerified(): bool
    {
        return $this->verified;
    }

    public function setVerified(bool $verified): self
    {
        $this->verified = $verified;
        return $this;
    }

    public function isAccountPubliclyVisible(): bool
    {
        return $this->accountPubliclyVisible;
    }

    public function setAccountPubliclyVisible(bool $accountPubliclyVisible): self
    {
        $this->accountPubliclyVisible = $accountPubliclyVisible;
        return $this;
    }

    public function getCurrentLocation(): ?string
    {
        return $this->currentLocation;
    }

    public function setCurrentLocation(?string $currentLocation): self
    {
        $this->currentLocation = $currentLocation;
        return $this;
    }

    public function addFollowee(User $user): self
    {
        if (!$this->following->contains($user)) {
            $this->following[] = $user;
        }
        return $this;
    }

    public function removeFollowee(User $user): self
    {
        if ($this->following->contains($user)) {
            $this->following->removeElement($user);
        }
        return $this;
    }

    public function likePost(Post $post): self
    {
        if (!$this->likedPosts->contains($post)) {
            $this->likedPosts[] = $post;
        }
        return $this;
    }

    public function addPostToFavorites(Post $post): self
    {
        if (!$this->favoritePosts->contains($post)) {
            $this->favoritePosts[] = $post;
        }
        return $this;
    }

    public function removePostFromFavorites(Post $post): self
    {
        if ($this->favoritePosts->contains($post)) {
            $this->favoritePosts->removeElement($post);
        }
        return $this;
    }

    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    public function setTimezone(?string $timezone): self
    {
        $this->timezone = $timezone;
        return $this;
    }

    public function getGoogleId(): ?string
    {
        return $this->googleId;
    }

    public function setGoogleId(?string $googleId): self
    {
        $this->googleId = $googleId;
        return $this;
    }

    public function getFacebookId(): ?string
    {
        return $this->facebookId;
    }

    public function setFacebookId(?string $facebookId): self
    {
        $this->facebookId = $facebookId;
        return $this;
    }

    public function isPatron(): bool
    {
        return $this->isPatron;
    }

    public function setIsPatron(bool $isPatron): self
    {
        $this->isPatron = $isPatron;
        return $this;
    }
}
