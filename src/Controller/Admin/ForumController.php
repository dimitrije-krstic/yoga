<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\FlaggedMessageThread;
use App\Model\Admin\FlaggedMessageThreadDataWrapper;
use App\Repository\FlaggedMessageThreadRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/forum")
 */
class ForumController extends AbstractController
{
    /**
     * @Route("/flagged/list", methods={"GET"}, name="app_admin_forum_flagged_list")
     */
    public function getFlaggedForums(
        Request $request,
        FlaggedMessageThreadRepository $flaggedMessageThreadRepository
    ): Response {
        $pagination = $flaggedMessageThreadRepository->getPaginatedFlaggedThreads(
            $request->query->getInt('page', 1)
        );

        $dataWrapper = [];
        if ($pagination->count() > 0) {
            foreach($pagination->getItems() as $item) {
                $dataWrapper[] = new FlaggedMessageThreadDataWrapper(
                    (int)$item['reportId'],
                    (int)$item['threadId'],
                    $item['authorSlug'],
                    $item['authorEmail'],
                    $item['flagCreatedAt'],
                    $item['flagUpdatedAt'],
                    (int)$item['flagStatus'],
                    $item['flagReason'],
                    $item['reportingMemberEmail']
                );
            }
        }

        /** @var FlaggedMessageThreadDataWrapper[] $dataWrapper */
        return $this->render(
            'admin/list_flagged_threads.html.twig',
            [
                'dataWrapper'  => $dataWrapper,
                'pagination' => $pagination
            ]
        );
    }

    /**
     * TODO change to POST
     * @Route("/flagged/{id}/status/{statusId}", methods={"GET"}, name="app_admin_forum_flagged_status")
     */
    public function changeStatusOfFlaggedForum(
        FlaggedMessageThread $flaggedMessageThread,
        int $statusId,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $flaggedMessageThread->setStatus($statusId);
        $entityManager->flush();
        $this->addFlash('success', 'Status changed');

        return $this->redirectToRoute(
            'app_admin_forum_flagged_list',
            $request->query->all()
        );
    }
}
