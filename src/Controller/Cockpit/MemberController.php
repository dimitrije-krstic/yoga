<?php
declare(strict_types=1);

namespace App\Controller\Cockpit;

use App\Entity\NotificationTracker;
use App\Entity\User;
use App\Repository\NotificationTrackerRepository;
use App\Services\MessageService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/cockpit/member")
 */
class MemberController extends AbstractController
{
    /**
     * Ajax
     *
     * @Route("/notifications/{slug}", methods={"GET"}, name="app_member_notifications")
     */
    public function getMemberNotifications(
        Request $request,
        User $member,
        NotificationTrackerRepository $notificationTrackerRepository,
        MessageService $messageService,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if (!$notificationTrackerRepository->usersAreConnected($member, $user)) {
            new Response();
        }

        $lastVisit = null;
        if ($tracker = $notificationTrackerRepository->findOneBy(['follower' => $user, 'followed' => $member])) {
            $lastVisit = $tracker->getUpdatedAt();
            $tracker->setUpdatedAt(new \DateTime());
            $entityManager->flush();
        }

        $threads = $messageService->getNotificationThreadsDataWrapper(
            $member,
            $lastVisit,
            $user
        );

        return $this->render(
            'network/_ajax_notification_list.html.twig',
            [
                'threads' => $threads,
                'networkNotificationTracker' => $tracker ? true : false
            ]
        );
    }

    /**
     * TODO CHANGE TO POST
     * @Route("/follow/{slug}", methods={"GET"}, name="app_member_follow")
     */
    public function followMember(
        User $member,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $user->addFollowee($member);

        $notificationTracker = new NotificationTracker($user, $member);
        $entityManager->persist($notificationTracker);

        $entityManager->flush();

        $this->addFlash('success', 'You are now following ' . explode(' ', $member->getName())[0]);
        $referer = $request->headers->get('referer');

        return $referer ? $this->redirect($referer) : $this->redirectToRoute('app_member_list', $request->query->all());
    }

    /**
     * TODO CHANGE TO POST
     * @Route("/unfollow/{slug}", methods={"GET"}, name="app_member_unfollow")
     */
    public function unfollowMember(
        User $member,
        Request $request,
        EntityManagerInterface $entityManager,
        NotificationTrackerRepository $notificationTrackerRepository
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $user->removeFollowee($member);

        if ($tracker = $notificationTrackerRepository->findOneBy(['follower' => $user, 'followed' => $member])) {
            $entityManager->remove($tracker);
        }

        $entityManager->flush();

        $this->addFlash('success', 'You stopped following ' . explode(' ', $member->getName())[0]);
        $referer = $request->headers->get('referer');

        return $referer ? $this->redirect($referer) : $this->redirectToRoute('app_member_list', $request->query->all());
    }
}
