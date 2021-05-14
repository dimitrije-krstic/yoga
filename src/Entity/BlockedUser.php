<?php
declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\BlockedUserRepository")
 */
class BlockedUser
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     * @var string
     */
    private $email;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    private $name;

    /**
     * @ORM\Column(type="datetime")
     * @var \DateTime
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime")
     * @var \DateTime
     */
    private $blockedAt;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    private $blockedBy;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    private $reason;

    public function __construct(
        string $email,
        string $name,
        \DateTime $createdAt,
        string $blockedBy,
        string $reason = ''
    ) {
        $this->email = $email;
        $this->name = $name;
        $this->createdAt = $createdAt;
        $this->blockedAt = new \DateTime();
        $this->blockedBy = $blockedBy;
        $this->reason = $reason;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function getBlockedAt(): \DateTime
    {
        return $this->blockedAt;
    }

    public function getBlockedBy(): string
    {
        return $this->blockedBy;
    }

    public function getReason(): string
    {
        return $this->reason;
    }
}
