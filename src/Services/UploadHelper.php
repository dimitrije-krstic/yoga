<?php
declare(strict_types=1);

namespace App\Services;

use App\Entity\User;
use Gedmo\Sluggable\Util\Urlizer;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadHelper
{
    public const UPLOAD_DIRECTORY = 'uploads';
    public const FORUM_IMAGE_DIRECTORY = self::UPLOAD_DIRECTORY.'/forum_images';

    public const POST_IMAGE_DIRECTORY = self::UPLOAD_DIRECTORY.'/post_images';
    public const POST_IMAGE_DIRECTORY_SMALL = self::POST_IMAGE_DIRECTORY.'/small';

    public const USER_IMAGE_DIRECTORY = self::UPLOAD_DIRECTORY.'/user_profile_images';
    public const USER_IMAGE_DIRECTORY_SMALL = self::USER_IMAGE_DIRECTORY .'/small';
    public const USER_DEFAULT_IMAGE_PATH = 'build/images/app/default-avatar.jpg';
    public const USER_DEFAULT_IMAGE_SMALL_PATH = 'build/images/app/default-avatar-small.jpg';

    private string $uploadsPath;

    public function __construct(string $uploadsPath)
    {
        $this->uploadsPath = $uploadsPath;
    }

    public function uploadPostImage(File $file): string
    {
        $filename = $this->uploadImage(
            $file,
            $this->uploadsPath.'/'.self::POST_IMAGE_DIRECTORY
        );

        list($width) = getimagesize($this->uploadsPath.'/'.self::POST_IMAGE_DIRECTORY.'/'.$filename);

        if ($width > 800) {
            $this->rescaleOnUpload(800, self::POST_IMAGE_DIRECTORY, $filename);
        }

        $this->createThumbnail(480, 360, self::POST_IMAGE_DIRECTORY, self::POST_IMAGE_DIRECTORY_SMALL, $filename);

        return $filename;
    }

    public function deletePostImage(string $filename): void
    {
        foreach ([self::POST_IMAGE_DIRECTORY, self::POST_IMAGE_DIRECTORY_SMALL] as $imageDir) {
            $this->deleteImage(
                $this->uploadsPath.'/'.$imageDir.'/'.$filename
            );
        }
    }

    public function uploadUserProfileImage(File $newImageFile, User $user): void
    {
        if (($oldImageFileName = $user->getProfileImage()) !== null) {
            $this->deleteUserProfileImage($oldImageFileName);
        }

        // UPLOAD image
        $newFilename = $this->uploadImage(
            $newImageFile,
            $this->uploadsPath.'/'.self::USER_IMAGE_DIRECTORY
        );

        // RESCALE after upload
        $image = new \Imagick($this->uploadsPath.'/'.self::USER_IMAGE_DIRECTORY.'/'. $newFilename);
        $image->cropThumbnailImage(240, 240);
        $image->writeImage();

        // CREATE Thumbnail
        $this->createThumbnail(
            80,
            80,
            self::USER_IMAGE_DIRECTORY,
            self::USER_IMAGE_DIRECTORY_SMALL,
            $newFilename
        );

        $user->setProfileImage($newFilename);
    }

    public function uploadUserProfileImageFromSocialNetwork(string $imageUrl): string
    {
        $filename = 'profile-image-'.uniqid().'.jpeg';
        $filePath = $this->uploadsPath.'/'.self::USER_IMAGE_DIRECTORY.'/'.$filename;

        file_put_contents($filePath , file_get_contents($imageUrl));

        // CREATE Thumbnail
        $this->createThumbnail(
            80,
            80,
            self::USER_IMAGE_DIRECTORY,
            self::USER_IMAGE_DIRECTORY_SMALL,
            $filename
        );

        return $filename;
    }

    public function deleteUserProfileImage(string $filename): void
    {
        foreach ([self::USER_IMAGE_DIRECTORY, self::USER_IMAGE_DIRECTORY_SMALL] as $imageDir) {
            $this->deleteImage(
                $this->uploadsPath.'/'.$imageDir.'/'.$filename
            );
        }
    }

    public function uploadForumImage(File $file): string
    {
        $filename = $this->uploadImage($file,$this->uploadsPath.'/'.self::FORUM_IMAGE_DIRECTORY);

        list($width) = getimagesize($this->uploadsPath.'/'.self::FORUM_IMAGE_DIRECTORY.'/'.$filename);

        if ($width > 480) {
            $this->rescaleOnUpload(480, self::FORUM_IMAGE_DIRECTORY, $filename);
        }

        return $filename;
    }

    public function deleteForumImage(string $filename): void
    {
        $this->deleteImage(
            $this->uploadsPath.'/'. self::FORUM_IMAGE_DIRECTORY .'/'.$filename
        );
    }

    private function rescaleOnUpload(int $width, string $directory, string $filename): void
    {
        $image = new \Imagick($this->uploadsPath.'/'.$directory.'/'. $filename);

        $image->resizeImage($width, 0, \Imagick::FILTER_LANCZOS2, 1, false);

        $image->writeImage($this->uploadsPath.'/'.$directory.'/'. $filename);
    }

    private function createThumbnail(int $width, int $height, string $sourceDir, string $targetDir, string $filename): void
    {
        $image = new \Imagick($this->uploadsPath.'/'.$sourceDir.'/'. $filename);

        $image->cropThumbnailImage($width, $height);

        $fs = new Filesystem();
        if (!$fs->exists($targetDir)) {
            $fs->mkdir($targetDir);
        }

        $image->writeImage($this->uploadsPath.'/'.$targetDir.'/'. $filename);
    }

    private function uploadImage(File $file, string $destination): string
    {
        if ($file instanceof UploadedFile) {
            $originalFilename = $file->getClientOriginalName();
        } else {
            $originalFilename = $file->getFilename();
        }

        $newFilename = Urlizer::urlize(pathinfo($originalFilename, PATHINFO_FILENAME)).'-'.uniqid().'.'.$file->guessExtension();

        $file->move(
            $destination,
            $newFilename
        );

        return $newFilename;
    }

    private function deleteImage(string $imagePath): void
    {
        $fs = new Filesystem();
        if ($fs->exists($imagePath)) {
            $fs->remove($imagePath);
        }
    }
}