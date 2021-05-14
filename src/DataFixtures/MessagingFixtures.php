<?php
declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Message;
use App\Entity\MessageThread;
use App\Entity\NotificationTracker;
use App\Entity\User;
use App\Services\UploadHelper;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class MessagingFixtures extends Fixture
{
    private const PASSWORD =  '12345678';
    private $passwordEncoder;
    private $uploadHelper;

    private $images = [
            'forum_1.jpg',
            'forum_2.jpg',
            'forum_3.jpg',
            'forum_4.jpg',
        ];

    public function __construct(UserPasswordEncoderInterface $passwordEncoder, UploadHelper $uploadHelper)
    {
        $this->passwordEncoder = $passwordEncoder;
        $this->uploadHelper = $uploadHelper;
    }

    public function load(ObjectManager $manager)
    {

        $user1 = $this->createUser($manager,'messages1@yoga.com','Michele Pub mOne', true);
        $user2 = $this->createUser($manager, 'messages2@yoga.com','Rice Shy mTwo', false);
        $user3 = $this->createUser($manager, 'messages3@yoga.com','Pradesh Pub mThree', true);
        $admin = $this->createUser($manager, 'messagesAdmin@yoga.com','Admin Notifications', false,true);

        $user1->addFollowee($user2);
        $notificationTracker1 = new NotificationTracker($user1, $user2);
        $manager->persist($notificationTracker1);

        $user2->addFollowee($user3);
        $notificationTracker2 = new NotificationTracker($user2, $user3);
        $manager->persist($notificationTracker2);

        $manager->flush();
        sleep(1);

        /** @var User[] $senders */
        $senders = [$user1, $user2, $user3, $admin];
        /** @var User[] $receivers */
        $receivers = [$user1, $user2, $user3, $admin];
        $faker = Factory::create();

        foreach ($senders as $sender) {
            for ($i=0; $i<10; $i++) {
                $receiver = $faker->randomElement($receivers);
                if ($sender->getId() !== $receiver->getId()) {
                    $thread = $this->createThread($manager, $sender, $receiver);
                    $this->newMessage($manager, $receiver, $thread);
                    $this->newMessage($manager, $sender, $thread);
                    $this->newMessage($manager, $receiver, $thread);
                }
            }

            for ($i=0; $i<4; $i++) {
                $notification = $this->createThread($manager, $sender, null, true);
                $this->newMessage($manager, $faker->randomElement($receivers), $notification);
                $this->newMessage($manager, $faker->randomElement($receivers), $notification);
                $this->newMessage($manager, $faker->randomElement($receivers), $notification);

                $forum = $this->createThread($manager, $sender, null, false);
                $this->newMessage($manager, $faker->randomElement($receivers), $forum);
                $this->newMessage($manager, $faker->randomElement($receivers), $forum);
                $this->newMessage($manager, $faker->randomElement($receivers), $forum);
            }
        }
    }

    private function newMessage(ObjectManager $manager, User $createdBy, MessageThread $thread): void
    {
        $faker = Factory::create();
        $message = new Message($createdBy);
        $message->setContent($faker->realText(100));
        $message->setThread($thread);

        $manager->persist($message);
        $manager->flush();
    }

    private function createThread(ObjectManager $manager, User $createdBy, ?User $receiver, bool $isNotification = false): MessageThread
    {
        $faker = Factory::create();

        if ($receiver) {
            $thread = MessageThread::createDirectMessageThread(
                $createdBy,
                $receiver,
                !$createdBy->isAcceptedTermsOfUse()
            );
            $subject = $faker->realText(50);
        } elseif ($isNotification) {
            $thread = MessageThread::createNotificationThread($createdBy, !$createdBy->isAcceptedTermsOfUse());
            $subject = 'NOTIFICATION: '.$faker->realText(50);
        } else {
            $thread = MessageThread::createForumThread($createdBy);
            $subject = 'FORUM: '.$faker->realText(50);
        }

        $thread->setSubject($subject);

        $message = new Message($createdBy);
        $message->setContent($faker->realText(200));
        $thread->addMessage($message);

        $manager->persist($thread);
        $manager->flush();

        return $thread;
    }

    private function createUser(ObjectManager $manager, string $email, string $name, bool $visible, $isAdmin = false): User
    {
        $faker = Factory::create();

        $user = new User(
            $email,
            $name,
            !$isAdmin
        );
        $user->setPassword(
            $this->passwordEncoder->encodePassword(
                $user,
                self::PASSWORD
            ));
        $user->setCreatedAt(
            $faker->dateTimeBetween('-100 days', '-90 days')
        );
        $user->setVerified(true);
        $user->setAccountPubliclyVisible($visible);

        $randomImage = array_shift($this->images);
        $fs = new Filesystem();
        $targetPath = sys_get_temp_dir().'/'.$randomImage;
        $fs->copy(__DIR__.'/images/'.$randomImage, $targetPath, true);
        $this->uploadHelper->uploadUserProfileImage(new File($targetPath), $user);

        $manager->persist($user);
        $manager->flush();

        return $user;
    }
}
