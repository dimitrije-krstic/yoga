<?php
declare(strict_types=1);

namespace App\Form\DataTransformer;

use App\Entity\Tag;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;

class TagToStringTransformer implements DataTransformerInterface
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param Collection|null $tagCollection
     * @return string
     */
    public function transform($tagCollection): string
    {
        if ($tagCollection === null) {
            return '';
        }

        $slugs = [];
        /** @var Tag $tag */
        foreach ($tagCollection->toArray() as $tag) {
            $slugs[] = $tag->getSlug();
        }

        return implode(' ', $slugs);
    }

    /**
     * @param string|null $slugsAsText
     * @return Collection|null
     */
    public function reverseTransform($slugsAsText): ?Collection
    {
        if (empty($slugsAsText)) {
            return new ArrayCollection();
        }

        $allTagsArray = array_map(function ($item){
            return mb_strtolower(str_replace(['#', ',', ';', ':', '.', '/', '|', '\\'],'', $item));
        }, explode(' ', trim($slugsAsText)));

        $allTagsArray = array_values(array_unique(array_filter($allTagsArray)));

        $existingTags = $this->entityManager
            ->getRepository(Tag::class)
            ->findBy(['slug' => $allTagsArray]);

        $existingSlugs = [];
        foreach ($existingTags as $tag) {
            $existingSlugs[] = $tag->getSlug();
        }

        $newSlugs = array_diff($allTagsArray, $existingSlugs);

        $newTags = [];
        foreach ($newSlugs as $slug) {
            $newTags[] = new Tag($slug);
        }

        return new ArrayCollection(array_merge($existingTags, $newTags));
    }
}

