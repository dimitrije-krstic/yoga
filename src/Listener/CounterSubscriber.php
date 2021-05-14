<?php
declare(strict_types=1);

namespace App\Listener;

use App\Entity\VisitorsCounter;
use App\Repository\VisitorsCounterRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class CounterSubscriber implements EventSubscriberInterface
{
    private const MONITORED_ROUTES = [
        'app_homepage',
        'app_public_post_list',
        'app_post_view_page',
        'app_member_public_forum',
        'app_member_list',
        'app_member_details',
        'app_contribute',
        'app_contact_us',
        'app_register',
        'app_login',
        'app_about_us'
    ];

    private EntityManagerInterface $entityManager;
    private VisitorsCounterRepository $visitorsCounterRepository;

    public function __construct(EntityManagerInterface $entityManager, VisitorsCounterRepository $visitorsCounterRepository)
    {
        $this->entityManager = $entityManager;
        $this->visitorsCounterRepository = $visitorsCounterRepository;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::TERMINATE => 'onTerminate',
        ];
    }

    public function onTerminate(TerminateEvent $event): void
    {
        $route = $event->getRequest()->attributes->get('_route');
        if (!in_array($route, self::MONITORED_ROUTES, true)) {
            return;
        }

        if ($counter = $this->visitorsCounterRepository->findOneBy(['route' => $route ])) {
            $this->visitorsCounterRepository->increase($route);

            return;
        }

        $counter = new VisitorsCounter($route);
        $this->entityManager->persist($counter);
        $this->entityManager->flush();
    }
}
