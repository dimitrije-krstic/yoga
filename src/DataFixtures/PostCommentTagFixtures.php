<?php
declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\PostComment;
use App\Entity\Post;
use App\Entity\Tag;
use App\Entity\User;
use App\Repository\PostRepository;
use App\Repository\TagRepository;
use App\Repository\UserRepository;
use App\Services\UploadHelper;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class PostCommentTagFixtures extends Fixture
{
    private const PASSWORD = '12345678';

    private static $images = [
        'post' => [
            'post_1.jpg',
            'post_2.jpg',
            'post_3.jpg',
            'post_4.jpg',
            'post_5.jpg',
            'post_6.jpg',
        ],
        'user' => [
            'user_1.jpg',
            'user_2.jpg',
            'user_3.jpg',
            'user_4.jpg',
            'user_5.jpg',
            'user_6.jpg',
        ],
        'video' => [
            'LEeMdzKSFp8',
            'O4EpkmNCAiA',
            'PTc8X37oJBE',
            'YBso7TPtvJU',
            'i7B4SspgC0w',
            'uuvNdfFelHk',
        ]
    ];

    private $passwordEncoder;
    private $uploadHelper;
    private $tagRepository;
    private $userRepository;
    private $postRepository;

    public function __construct(
        UserPasswordEncoderInterface $passwordEncoder,
        UploadHelper $uploadHelper,
        TagRepository $tagRepository,
        UserRepository $userRepository,
        PostRepository $postRepository
    ) {
        $this->passwordEncoder = $passwordEncoder;
        $this->uploadHelper = $uploadHelper;
        $this->tagRepository = $tagRepository;
        $this->userRepository = $userRepository;
        $this->postRepository = $postRepository;
    }

    public function load(ObjectManager $manager)
    {
        $faker = Factory::create();

        /** @var User[] $authorCollection */
        $authorCollection = $this->createAuthors($faker, $manager);
        /** @var Tag[] $tagsCollection */
        $tagsCollection = $this->createTags($manager);

        $this->createPosts($authorCollection, $tagsCollection, $faker, $manager);
        $this->createVideos($authorCollection, $tagsCollection, $faker, $manager);
        $pagination = $this->postRepository->getLatestPublishedPosts(1, 40);
        /** @var Post[] $postCollection */
        $postCollection = $pagination->getItems();

        // ADD COMMENTS TO, LIKE AND ADD POSTS TO FAVORITES
        foreach ($authorCollection as $author) {
            if ($faker->boolean) {
                $author->likePost($postCollection[random_int(0, count($postCollection) - 1)]);
                $author->likePost($postCollection[random_int(0, count($postCollection) - 1)]);
            }

            if ($faker->boolean(30)) {
                $author->addPostToFavorites($postCollection[random_int(0, count($postCollection) - 1)]);
                $author->addPostToFavorites($postCollection[random_int(0, count($postCollection) - 1)]);
            }

            for ($i = 0; $i < 6; $i++) {
                $comment = new PostComment($postCollection[random_int(0, count($postCollection) - 1)], $author);
                $comment->setContent($faker->realText(100));
                $manager->persist($comment);
            }

            $manager->flush();
        }
    }

    private function createAuthors($faker, $manager): array
    {
        // REGISTER AUTHORS
        for ($i=1; $i<11; $i++) {
            $public = $faker->boolean();
            $verified = $faker->boolean();
            $image = $faker->boolean(70);
            $patron = $faker->boolean(20);

            $author = new User(
                'author' . $i . '0@yoga.com',
                ($patron ? 'Patron' : $faker->firstName) .' Author' . ($public ? ' Pub' : '') . ($verified ? ' Ver' : '') . ($image ? ' Img' : ''),
                true
            );
            $author->setPassword(
                $this->passwordEncoder->encodePassword(
                    $author,
                    self::PASSWORD
                ));
            $author->setCreatedAt(
                $faker->dateTimeBetween('-100 days', '-50 days')
            );
            $author->setVerified($verified);
            $author->setAccountPubliclyVisible($public);
            $author->setCurrentLocation($faker->city . ', ' . $faker->country);
            $this->setUserInfo($author);
            if ($patron) {
                $author->setIsPatron(true);
            }

            if ($image) {
                $this->fakeUploadImage('user', $author);
            }

            $manager->persist($author);
            $manager->flush();
        }

        return $this->userRepository->findAll();
    }

    private function setUserInfo(User $user): void
    {
        $faker = Factory::create();

        $user->getUserInfo()
            ->setIntroduction($faker->text(500))
            ->setFacebookAccount($faker->boolean(70) ? $faker->url : null)
            ->setGoogleAccount($faker->boolean(70) ? $faker->url : null)
            ->setInstagramAccount($faker->boolean(70) ? $faker->url : null)
            ->setTwitterAccount($faker->boolean(70) ? $faker->url : null)
            ->setYoutubeAccount($faker->boolean(70) ? $faker->url : null)
            ->setLinkedinAccount($faker->boolean(70) ? $faker->url : null)
            ->setPersonalWebsite($faker->boolean(70) ? $faker->url : null);
    }

    private function createTags($manager): array
    {
        $slugs = ['kids-yoga', 'india', 'ashram', 'vegan', 'meditation', 'kirtan', 'asanas', 'yoga-in-pregnancy'];
        foreach ($slugs as $slug) {
            $tagDto = new Tag($slug);
            $manager->persist($tagDto);
        }
        $manager->flush();

        return $this->tagRepository->findAll();
    }

    private function createPosts($authorCollection, $tagsCollection, $faker, $manager): void
    {
        for ($i=0; $i<20; $i++) {
            $tag = $tagsCollection[random_int(0, count($tagsCollection) - 1)];
            $category = random_int(1, 10);
            $author = $authorCollection[random_int(0, count($authorCollection) - 1)];
            $carousel = $faker->boolean;
            $published = $faker->boolean(70);
            $images = $faker->boolean(80);

            $post = new Post($author);
            $post->setTitle(
                $faker->country.' cat-'.$category.($carousel ? ' Carusel' : '').($images ? ' IMG': '').($published ? ' PUB' :''). ' Tag-'. $tag->getSlug()
            )
                ->setContent($faker->paragraphs(random_int(3, 9), true))
                ->addTag($tag)
                ->setCreatedAt($faker->dateTimeBetween('-49 days', '-30 days'))
                //->setParagraphCountToInsertImage($carousel ? 0 : 1)
                ->setCategory($category);

            if ($published) {
                $post->setPublishedAt($faker->dateTimeBetween('-29 days', '-20 days'));
            }

            if ($images) {
                $post->addImage($this->fakeUploadImage())
                    ->addImage($this->fakeUploadImage())
                    ->addImage($this->fakeUploadImage());
            }

            $manager->persist($post);
        }

        $manager->flush();
    }

    private function createVideos($authorCollection, $tagsCollection, $faker, $manager): void
    {
        // VIDEO ARTICLES
        for ($i=0; $i<20; $i++) {
            $published = $faker->boolean(70);
            $author = $authorCollection[random_int(0, count($authorCollection) - 1)];
            $category = random_int(1, 10);

            $video = new Post($author);
            $video->setTitle(
                $faker->country.' Video'.($published ? ' PUB' : '').' mTAG'. ' cat-'.$category
            )
                ->setContent($faker->paragraphs(random_int(3, 9), true))
                ->addTag($tagsCollection[random_int(0,count($tagsCollection)-1)])
                ->addTag($tagsCollection[random_int(0,count($tagsCollection)-1)])
                ->addTag($tagsCollection[random_int(0,count($tagsCollection)-1)])
                ->addTag(new Tag($faker->word.$i))
                ->setYoutubeVideoId(self::$images['video'][random_int(0, 5)])
                ->setCreatedAt($faker->dateTimeBetween('-49 days', '-30 days'))
                ->setCategory($category);

            if ($published) {
                $video->setPublishedAt($faker->dateTimeBetween('-29 days', '-20 days'));
            }

            $manager->persist($video);
        }
        $manager->flush();
    }

    public function fakeUploadImage(string $type = 'post', User $user = null): string
    {
        $faker = Factory::create();

        $randomImage = $faker->randomElement(self::$images[$type]);
        $fs = new Filesystem();
        $targetPath = sys_get_temp_dir().'/'.$randomImage;
        $fs->copy(__DIR__.'/images/'.$randomImage, $targetPath, true);

        if ($type === 'post') {
            return $this->uploadHelper->uploadPostImage(new File($targetPath));
        }

        if ($user) {
            $this->uploadHelper->uploadUserProfileImage(new File($targetPath), $user);
        }

        return '';
    }
}
