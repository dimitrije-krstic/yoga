<?php
declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Event;
use App\Entity\EventComment;
use App\Entity\EventReview;
use App\Entity\EventTracker;
use App\Entity\User;
use App\Services\UploadHelper;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class EventCommentFixtures extends Fixture
{
    private const PASSWORD = '12345678';

    private static $images = [
        'user_1.jpg',
        'user_2.jpg',
        'user_3.jpg',
        'user_4.jpg',
        'user_5.jpg',
        'user_6.jpg',
    ];

    private $passwordEncoder;
    private $uploadHelper;

    public function __construct(
        UserPasswordEncoderInterface $passwordEncoder,
        UploadHelper $uploadHelper
    ) {
        $this->passwordEncoder = $passwordEncoder;
        $this->uploadHelper = $uploadHelper;
    }

    public function load(ObjectManager $manager)
    {
        $faker = Factory::create();

        /** @var User[] $authorCollection */
        $authorCollection = $this->createAuthors($faker, $manager);

        $events = $this->createEvents($authorCollection, $faker, $manager);

        $this->addParticipantsCommentsAndReviews($authorCollection, $events, $faker, $manager);
    }

    private function createAuthors($faker, $manager): array
    {
        $authorList = [];
        // REGISTER AUTHORS
        for ($i=1; $i<3; $i++) {
            $author = new User(
                'event' . $i . '0@yoga.com',
                $faker->firstName .' Event-'.$i,
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
            $author->setVerified(true);
            $author->setAccountPubliclyVisible(true);
            $author->setCurrentLocation($faker->city . ', ' . $faker->country);
            $this->setUserInfo($author);
            $author->setTimezone($faker->timezone);
            $this->fakeUploadImage($author);

            $authorList[] = $author;
            $manager->persist($author);
            $manager->flush();
        }

        return $authorList;
    }

    private function setUserInfo(User $user): void
    {
        $faker = Factory::create();

        $user->getUserInfo()
            ->setIntroduction($faker->realText(500))
            ->setFacebookAccount($faker->boolean(70) ? $faker->url : null)
            ->setGoogleAccount($faker->boolean(70) ? $faker->url : null)
            ->setInstagramAccount($faker->boolean(70) ? $faker->url : null)
            ->setTwitterAccount($faker->boolean(70) ? $faker->url : null)
            ->setYoutubeAccount($faker->boolean(70) ? $faker->url : null)
            ->setLinkedinAccount($faker->boolean(70) ? $faker->url : null)
            ->setPersonalWebsite($faker->boolean(70) ? $faker->url : null);
    }

    private function createEvents($authorCollection, $faker, $manager): array
    {
        $allEvents = [];
        foreach ($authorCollection as $author) {
            for ($i=0; $i<2; $i++) {

                // NOT PUBLISHED
                $event = $this->getEvent($author, $faker);
                $event
                    ->setCreatedAt($faker->dateTimeBetween('-30 days', '-20 days'))
                    ->setStart($faker->dateTimeBetween('+30 days', '+40 days'))
                ;
                $manager->persist($event);
                $allEvents[] = $event;

                // NOT PUBLISHED and LAPSED
                $event = $this->getEvent($author, $faker);
                $event
                    ->setCreatedAt($faker->dateTimeBetween('-30 days', '-20 days'))
                    ->setStart($faker->dateTimeBetween('-19 days', '-15 days'))
                ;
                $manager->persist($event);
                $allEvents[] = $event;

                // PUBLISHED UPCOMING
                $event = $this->getEvent($author, $faker);
                $event
                    ->setCreatedAt($faker->dateTimeBetween('-30 days', '-20 days'))
                    ->setStart($faker->dateTimeBetween('+30 days', '+40 days'))
                    ->setPublished($faker->dateTimeBetween('-9 days', '-5 days'))
                ;
                $manager->persist($event);
                $allEvents[] = $event;

                // PUBLISHED UPCOMING CANCELED
                $event = $this->getEvent($author, $faker);
                $event
                    ->setCreatedAt($faker->dateTimeBetween('-30 days', '-20 days'))
                    ->setStart($faker->dateTimeBetween('+10 days', '+20 days'))
                    ->setPublished($faker->dateTimeBetween('-9 days', '-5 days'))
                    ->setCancelled($faker->dateTimeBetween('-4 days', '-2 days'))
                ;
                $manager->persist($event);
                $allEvents[] = $event;

                // PUBLISHED PASSED
                $event = $this->getEvent($author, $faker);
                $event
                    ->setCreatedAt($faker->dateTimeBetween('-30 days', '-20 days'))
                    ->setPublished($faker->dateTimeBetween('-19 days', '-15 days'))
                    ->setStart($faker->dateTimeBetween('-14 days', '-10 days'))
                ;
                $manager->persist($event);
                $allEvents[] = $event;

                // PUBLISHED PASSED
                $event = $this->getEvent($author, $faker);
                $event
                    ->setCreatedAt($faker->dateTimeBetween('-30 days', '-20 days'))
                    ->setPublished($faker->dateTimeBetween('-19 days', '-15 days'))
                    ->setStart($faker->dateTimeBetween('-14 days', '-10 days'))
                ;
                $manager->persist($event);
                $allEvents[] = $event;

                // PUBLISHED PASSED and CANCELLED
                $event = $this->getEvent($author, $faker);
                $event
                    ->setCreatedAt($faker->dateTimeBetween('-30 days', '-20 days'))
                    ->setPublished($faker->dateTimeBetween('-19 days', '-15 days'))
                    ->setCancelled($faker->dateTimeBetween('-14 days', '-10 days'))
                    ->setStart($faker->dateTimeBetween('-9 days', '-1 day'))
                ;
                $manager->persist($event);
                $allEvents[] = $event;

            }
            $manager->flush();
        }

        return $allEvents;
    }

    private function getEvent(User $author, $faker): Event
    {
        $duration = [30, 45, 60, 90, 120, 180, 111, 222];
        $timezones = [
            'America/Los_Angeles',
            'America/Denver',
            'America/Chicago',
            'America/New_York',
            'Europe/London',
            'Europe/Brussels',
            'Africa/Johannesburg',
            'Asia/Calcutta',
            'Australia/Perth',
            'Australia/Sydney'
        ];

        $event = new Event($author);
        $event->setTitle($faker->sentence(4) .' '. $faker->country)
            ->setDescription($faker->paragraphs(random_int(3, 9), true))
            ->setLink($faker->boolean(50) ? $faker->url : null)
            ->setLinkPassword($faker->boolean(50) ? '123ABCxyz!?' : null)
            ->setCategory(random_int(1, 5))
            ->setTimezone($timezones[random_int(0, 9)])
            ->setDuration($duration[random_int(0, 7)])
        ;

        return $event;
    }

    private function addParticipantsCommentsAndReviews($authorCollection, $events, $faker, $manager): void
    {
        /** @var Event $event */
        foreach ($events as $event) {
            if ($event->getPublished() === null || $event->getId() % 2 !== 0) {
                continue;
            }

            for ($i = 0; $i < 4; $i++) {
                $comment = new EventComment(
                    $authorCollection[random_int(0, count($authorCollection) - 1)],
                    $event
                );
                $comment->setContent($faker->realText(100));
                $event->addComment($comment);
            }
            $manager->flush();

            foreach ($authorCollection as $participant) {
                if ($event->getOrganizer()->getId() === $participant->getId()) {
                    continue;
                }

                $event->addParticipant($participant);
                $tracker = new EventTracker($participant, $event);
                $manager->persist($tracker);
                $manager->flush();
            }
        }
    }

    private function fakeUploadImage(User $user): void
    {
        $faker = Factory::create();

        $randomImage = $faker->randomElement(self::$images);
        $fs = new Filesystem();
        $targetPath = sys_get_temp_dir().'/'.$randomImage;
        $fs->copy(__DIR__.'/images/'.$randomImage, $targetPath, true);

        $this->uploadHelper->uploadUserProfileImage(new File($targetPath), $user);
    }
}
