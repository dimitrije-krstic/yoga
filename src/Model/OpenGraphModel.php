<?php
declare(strict_types=1);

namespace App\Model;

class OpenGraphModel
{
    private string $siteName;
    private string $title;
    private string $url;
    private string $image;

    public function __construct(
        string $siteName = '',
        string $title = '',
        string $url = '',
        string $image = ''
    ) {
        $this->siteName = $siteName;
        $this->title = $title;
        $this->url = $url;
        $this->image = $image;
    }

    public function getSiteName(): string
    {
        return $this->siteName;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getImage(): string
    {
        return $this->image;
    }
}