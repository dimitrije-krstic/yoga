<?php
declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class CreateWebMasterCommand extends Command
{
    protected static $defaultName = 'app:create:web-master';

    private EntityManagerInterface $entityManager;
    private string $appSecret;
    private UserRepository $userRepository;
    private UserPasswordEncoderInterface $passwordEncoder;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        UserPasswordEncoderInterface $passwordEncoder,
        string $appSecret
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->appSecret = $appSecret;
        $this->userRepository = $userRepository;
        $this->passwordEncoder = $passwordEncoder;
    }

    public function configure(): void
    {
        $this
            ->setDescription(
                'Create Web-Master if not created.'
            )->addOption(
                'password',
                null,
                InputOption::VALUE_REQUIRED,
                'Password for web-master.'
            )->addOption(
                'email',
                null,
                InputOption::VALUE_REQUIRED,
                'Email for web-master.'
            )->addOption(
                'secret',
                null,
                InputOption::VALUE_REQUIRED,
                'App secret.'
            );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getOption('secret') !== $this->appSecret) {
            $output->writeln('Wrong secret');

            return 1;
        }

        if ($this->userRepository->getMasterAdminUser()) {
            $output->writeln('Web Master already created');

            return 1;
        }

        $master = new User(
            $input->getOption('email'),
            'WebAdmin',
            false,
            ['ROLE_MASTER'],
        );

        $master->setPassword($this->passwordEncoder->encodePassword(
            $master,
            $input->getOption('password')
        ));

        $master->setVerified(true);
        $master->setAccountPubliclyVisible(false);

        $this->entityManager->persist($master);
        $this->entityManager->flush();

        $output->writeln('Web Master created');

        return 0;
    }
}