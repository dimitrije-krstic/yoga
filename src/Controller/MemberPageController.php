<?php
declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Model\Cockpit\MemberDataWrapper;
use App\Repository\EventRepository;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use App\Services\MemberNetworkService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @Route("/member")
 */
class MemberPageController extends AbstractController
{
    /**
     * @Route("/list", methods={"GET"}, name="app_member_list")
     */
    public function getMembers(
        Request $request,
        UserRepository $userRepository
    ): Response {
        // registered users can see all members
        $showAllMembers = $this->getUser() !== null;

        $pagination = $userRepository->getPaginatedUsers(
            $showAllMembers,
            $request->query->getInt('page', 1),
            $query = $request->query->get('query')
        );

        if (!empty($query) && $pagination->count() === 0) {
            $this->addFlash('error', 'Sorry, no members matching search criteria');
        }

        $members = [];
        /** @var  User $member */
        foreach ($pagination->getItems() as $member) {
            $members[] = new MemberDataWrapper(
                $member->getName(),
                $member->getSlug(),
                $member->getProfileImage(),
                false,
                $member->isPatron()
            );
        }

        return $this->render(
            'member/member_list.html.twig',
            [
                'pagination' => $pagination,
                'members' => $members,
            ]
        );
    }

    /**
     * @Route("/followers", methods={"GET"}, name="app_member_followers")
     * @IsGranted("ROLE_USER")
     */
    public function getFollowers(Request $request, UserRepository $userRepository): Response
    {
        $pagination = $userRepository->getPaginatedFollowers(
            $this->getUser(),
            $request->query->getInt('page', 1),
            $query = $request->query->get('query')
        );

        if (!empty($query) && $pagination->count() === 0) {
            $this->addFlash('error', 'Sorry, no members matching search criteria');
        }

        $members = [];
        /** @var  User $member */
        foreach ($pagination->getItems() as $member) {
            $members[] = new MemberDataWrapper(
                $member->getName(),
                $member->getSlug(),
                $member->getProfileImage(),
                false,
                $member->isPatron()
            );
        }

        return $this->render(
            'member/member_list.html.twig',
            [
                'members' => $members,
                'pagination' => $pagination,
                'tracking' => false,
            ]
        );
    }

    /**
     * @Route("/following", methods={"GET"}, name="app_member_following")
     * @IsGranted("ROLE_USER")
     */
    public function getNetworkNotificationsOverview(
        Request $request,
        UserRepository $userRepository,
        MemberNetworkService $memberNetworkService
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $pagination = $userRepository->getPaginatedFollowees(
            $user,
            $request->query->getInt('page', 1),
            $query = $request->query->get('query')
        );

        if (!empty($query) && $pagination->count() === 0) {
            $this->addFlash('error', 'Sorry, no members matching search criteria');
        }

        $members = $memberNetworkService->getMembersDataWrapper($pagination->getItems(), $user);

        return $this->render(
            'member/member_list.html.twig',
            [
                'members' => $members,
                'pagination' => $pagination,
                'tracking' => true,
            ]
        );
    }

    /**
     * Ajax
     * @Route("/{slug}", methods={"GET"}, name="app_member_details")
     *
     */
    public function getMemberDetails(
        string $slug,
        UserRepository $userRepository,
        PostRepository $postRepository,
        EventRepository $eventRepository
    ): Response {
        // registered users can see all members
        $showAll = $this->getUser() !== null;

        if (!$member = $userRepository->getActiveUser($slug, $showAll)) {
            return new Response("", 404);
        }

        /** @var User|null $user */
        $user = $this->getUser();
        $followedByUser = $user && $userRepository->isMemberFollowedByUser($user, $member);

        return $this->render(
            'member/_ajax_member_info.html.twig',
            [
                'member' => $member,
                'followedByUser' => $followedByUser,
                'eventsCount' => $eventRepository->countUpcomingEventsForMember($member),
                'postCount' => $postRepository->countPublishedPostsForMember($member)
            ]
        );
    }
}
