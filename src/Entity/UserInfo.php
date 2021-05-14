<?php
declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserInfoRepository")
 */
class UserInfo
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @var string|null
     */
    private $introduction;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string|null
     */
    private $personalWebsite;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string|null
     */
    private $facebookAccount;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string|null
     */
    private $youtubeAccount;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string|null
     */
    private $instagramAccount;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string|null
     */
    private $twitterAccount;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string|null
     */
    private $googleAccount;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string|null
     */
    private $linkedinAccount;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): UserInfo
    {
        $this->id = $id;
        return $this;
    }

    public function getIntroduction(): ?string
    {
        return $this->introduction;
    }

    public function setIntroduction(?string $introduction): UserInfo
    {
        $this->introduction = $introduction;
        return $this;
    }

    public function getPersonalWebsite(): ?string
    {
        return $this->personalWebsite;
    }

    public function setPersonalWebsite(?string $personalWebsite): UserInfo
    {
        $this->personalWebsite = $personalWebsite;
        return $this;
    }

    public function getFacebookAccount(): ?string
    {
        return $this->facebookAccount;
    }

    public function setFacebookAccount(?string $facebookAccount): UserInfo
    {
        $this->facebookAccount = $facebookAccount;
        return $this;
    }

    public function getYoutubeAccount(): ?string
    {
        return $this->youtubeAccount;
    }

    public function setYoutubeAccount(?string $youtubeAccount): UserInfo
    {
        $this->youtubeAccount = $youtubeAccount;
        return $this;
    }

    public function getInstagramAccount(): ?string
    {
        return $this->instagramAccount;
    }

    public function setInstagramAccount(?string $instagramAccount): UserInfo
    {
        $this->instagramAccount = $instagramAccount;
        return $this;
    }

    public function getTwitterAccount(): ?string
    {
        return $this->twitterAccount;
    }

    public function setTwitterAccount(?string $twitterAccount): UserInfo
    {
        $this->twitterAccount = $twitterAccount;
        return $this;
    }

    public function getGoogleAccount(): ?string
    {
        return $this->googleAccount;
    }

    public function setGoogleAccount(?string $googleAccount): UserInfo
    {
        $this->googleAccount = $googleAccount;
        return $this;
    }

    public function getIntroductionInParagraphs(): array
    {
        return $this->introduction ? array_values(array_filter(array_map('trim', explode(PHP_EOL, $this->introduction )))) : [];
    }

    public function getLinkedinAccount(): ?string
    {
        return $this->linkedinAccount;
    }

    public function setLinkedinAccount(?string $linkedinAccount): UserInfo
    {
        $this->linkedinAccount = $linkedinAccount;
        return $this;
    }
}
