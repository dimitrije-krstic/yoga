<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\FlaggedPost;
use App\Model\Admin\FlaggedPostDataWrapper;
use App\Repository\FlaggedPostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/post")
 */
class PostController extends AbstractController
{
    /**
     * @Route("/flagged/list", methods={"GET"}, name="app_admin_post_flagged_list")
     */
    public function getFlaggedPosts(
        Request $request,
        FlaggedPostRepository $flaggedPostRepository
    ): Response {
        $pagination = $flaggedPostRepository->getPaginatedFlaggedPosts(
            $request->query->getInt('page', 1)
        );

        $dataWrapper = [];
        if ($pagination->count() > 0) {
            foreach($pagination->getItems() as $item) {
                $dataWrapper[] = new FlaggedPostDataWrapper(
                    (int) $item['postId'],
                    $item['postSlug'],
                    $item['authorSlug'],
                    $item['authorEmail'],
                    (int) $item['reportId'],
                    $item['flagCreatedAt'],
                    $item['flagUpdatedAt'],
                    (int) $item['flagStatus'],
                    $item['flagReason'],
                    $item['reportingMemberEmail']
                );
            }
        }

        /** @var FlaggedPostDataWrapper[] $dataWrapper */
        return $this->render(
            'admin/list_flagged_posts.html.twig',
            [
                'dataWrapper'  => $dataWrapper,
                'pagination' => $pagination
            ]
        );
    }

    /**
     * TODO change to POST
     * @Route("/flagged/{id}/status/{statusId}", methods={"GET"}, name="app_admin_post_flagged_status")
     */
    public function changeStatusOfFlaggedPost(
        FlaggedPost $flaggedPost,
        int $statusId,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        switch ($statusId) {
            case 2; $flaggedPost->setStatusFalseClaim(); break;
            case 3; $flaggedPost->setStatusMarkedInappropriate(); break;
            case 4; $flaggedPost->setStatusResolved(); break;
        }

        $flaggedPost->getPost()->setMarkedAsInappropriateAt(
            $statusId === 3 ? new \DateTime() : null
        );

        $entityManager->flush();
        $this->addFlash('success', 'Status changed');

        return $this->redirectToRoute(
            'app_admin_post_flagged_list',
            $request->query->all()
        );
    }
}
