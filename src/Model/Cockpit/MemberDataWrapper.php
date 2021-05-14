<?php
declare(strict_types=1);

namespace App\Model\Cockpit;

use App\Services\UploadHelper;

class MemberDataWrapper
{
    private $name;
    private $slug;
    private $image;
    private $hasUpdates;
    private bool $isPatron;

    public function __construct(
        string $name,
        string $slug,
        ?string $image,
        bool $hasUpdates,
        bool $isPatron
    ) {
        $this->name = $name;
        $this->slug = $slug;
        $this->image = $image;
        $this->hasUpdates = $hasUpdates;
        $this->isPatron = $isPatron;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function hasUpdates(): bool
    {
        return $this->hasUpdates;
    }

    public function getImage(): string
    {
        return empty($this->image) ? UploadHelper::USER_DEFAULT_IMAGE_PATH :
            UploadHelper::USER_IMAGE_DIRECTORY.'/'.$this->image;
    }

    public function isPatron(): bool
    {
        return $this->isPatron;
    }
}
