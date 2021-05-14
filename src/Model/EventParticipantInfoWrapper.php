<?php
declare(strict_types=1);

namespace App\Model;

use App\Services\UploadHelper;

class EventParticipantInfoWrapper
{
    private string $name;
    private string $slug;
    private ?string $profileImage;

    public function __construct(
        string $name,
        string $slug,
        ?string $profileImage
    ) {
        $this->name = $name;
        $this->slug = $slug;
        $this->profileImage = $profileImage;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getProfileImage(): string
    {
        return empty($this->profileImage) ? UploadHelper::USER_DEFAULT_IMAGE_SMALL_PATH :
            UploadHelper::USER_IMAGE_DIRECTORY_SMALL.'/'.$this->profileImage ;
    }
}
