<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Repository\VisitorsCounterRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin")
 */
class ControlPanelController extends AbstractController
{
    /**
     * @Route("", methods={"GET"}, name="app_admin_control_panel")
     */
    public function getAdminControlPanel(VisitorsCounterRepository $visitorsCounterRepository): Response
    {
        $counters = $visitorsCounterRepository->findAll();

        return $this->render(
            'admin/control_panel.html.twig',
            [
                'counter' => $counters
            ]
        );
    }
}
