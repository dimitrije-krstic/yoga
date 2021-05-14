<?php
declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\NotificationTracker;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserNetworkFixtures extends Fixture
{
    private const PASSWORD =  '12345678';
    private $passwordEncoder;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
    }

    public function load(ObjectManager $manager)
    {
        $faker = Factory::create();

        // CREATE USER 1
            $user1 = new User(
                'network1@yoga.com',
                'User One',
                true
            );
            $user1->setPassword(
                $this->passwordEncoder->encodePassword(
                    $user1,
                    self::PASSWORD
            ));
            $user1->setCreatedAt(
                $faker->dateTimeBetween('-100 days', '-90 days')
            );
            $user1->setVerified(true);
            $manager->persist($user1);

        // CREATE USER 2
            $user2 = new User(
                'network2@yoga.com',
                'User Two',
                true
            );
            $user2->setPassword(
                $this->passwordEncoder->encodePassword(
                    $user2,
                    self::PASSWORD
                ));
            $user2->setCreatedAt(
                $faker->dateTimeBetween('-89days', '-80 days')
            );
            $user2->setVerified(true);
            $manager->persist($user2);

        // CREATE USER 3
        $user3 = new User(
            'network3@yoga.com',
            'User three',
            true
        );
        $user3->setPassword(
            $this->passwordEncoder->encodePassword(
                $user3,
                self::PASSWORD
            ));
        $user3->setCreatedAt(
            $faker->dateTimeBetween('-78 days', '-70 days')
        );
        $user3->setVerified(true);
        $manager->persist($user3);

        $manager->flush();

        $user1->addFollowee($user2);
        $notificationTracker1 = new NotificationTracker($user1, $user2);
        $manager->persist($notificationTracker1);

        $user2->addFollowee($user3);
        $notificationTracker2 = new NotificationTracker($user2, $user3);
        $manager->persist($notificationTracker2);
        $manager->flush();
    }
}
