<?php
declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\UserInfo;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserFixtures extends Fixture
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

        // ADMIN
            $admin = new User(
                'admin@yoga.com',
                $faker->name,
                false,
                ['ROLE_ADMIN'],
            );
            $admin->setPassword($this->passwordEncoder->encodePassword(
                $admin,
                self::PASSWORD
            ));
            $admin->setCreatedAt(
                $faker->dateTimeBetween('-10 days', '-1 days')
            );
            $admin->setVerified(true);
            $manager->persist($admin);
            $manager->flush();

        // WEB MASTER
            $master = new User(
                'admin@weareyogis.net',
                $faker->name,
                false,
                ['ROLE_MASTER'],
            );
            $master->setPassword($this->passwordEncoder->encodePassword(
                $master,
                self::PASSWORD
            ));
            $master->setCreatedAt(
                $faker->dateTimeBetween('-100 days', '-11 days')
            );
            $master->setVerified(true);
            $manager->persist($master);
            $manager->flush();
    }
}
