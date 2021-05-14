<?php
declare(strict_types=1);

namespace App\Listener;

use Flagception\Manager\FeatureManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Twig\Environment;

class MaintenanceListener
{
    private $isLocked;
    private $twig;
    private $featureManager;

    public function __construct(bool $isLocked, Environment $twig, FeatureManagerInterface $featureManager)
    {
        $this->isLocked = $isLocked;
        $this->twig = $twig;
        $this->featureManager = $featureManager;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$this->isLocked || $this->featureManager->isActive('site_maintenance_unlock')) {
            return;
        }

        $content = $this->twig->render('homepage/maintenance.html.twig');

        $event->setResponse(
            new Response(
                $content,
                Response::HTTP_SERVICE_UNAVAILABLE
            )
        );
    }
}
