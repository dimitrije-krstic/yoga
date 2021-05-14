<?php
declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\VisitorsCounterRepository")
 */
class VisitorsCounter
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\Column(type="string", unique=true)
     * @var string
     */
    private $route;

    /**
     * @ORM\Column(type="integer")
     * @var int
     */
    private $count;

    public function __construct(string $route)
    {
        $this->route = $route;
        $this->count = 1;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getRoute(): string
    {
        return $this->route;
    }

    public function getCount(): int
    {
        return $this->count;
    }
}
