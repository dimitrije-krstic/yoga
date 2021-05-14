<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\BlockedUserRepository;
use App\Repository\UserRepository;
use App\Services\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @Route("/admin/user")
 */
class UserController extends AbstractController
{
    /**
     * @Route("/list", methods={"GET"}, name="app_admin_get_users")
     */
    public function getUsers(
        Request $request,
        UserRepository $userRepository
    ): Response {
        $paginatedUsers = $userRepository->getPaginatedUsers(
            true,
            $request->query->getInt('page', 1),
            $request->query->get('query')
        );

        return $this->render(
            'admin/user/list_users.html.twig',
            [
                'paginatedUsers' => $paginatedUsers,
            ]
        );
    }

    /**
     * @Route("/blocked", methods={"GET", "POST"}, name="app_admin_blocked_users")
     */
    public function blockedUsers(
        Request $request,
        UserRepository $userRepository,
        BlockedUserRepository $userBlockedRepository,
        CsrfTokenManagerInterface $csrfTokenManager,
        UserService $userService
    ): Response {
        if ($request->getMethod() === 'POST' && $request->request->has('email')) {
            $email = $request->request->get('email');

            $token = new CsrfToken('authenticate', $request->request->get('_csrf_token'));
            if (!$csrfTokenManager->isTokenValid($token)) {
                throw new InvalidCsrfTokenException('Invalid token');
            }

            if (($userToBlock = $userRepository->findOneBy(['email' => $email]))
                && $userToBlock->getDeletedAt() === null
                && !(in_array('ROLE_ADMIN', $userToBlock->getRoles()) || in_array('ROLE_MASTER', $userToBlock->getRoles()))
            ) {
                $userService->blockUser(
                    $userToBlock,
                    $this->getUser(),
                    $request->request->get('reason', '')
                );

                $this->addFlash('success', 'User added to block list');
            }

            $this->redirectToRoute('app_admin_blocked_users');
        }

        $paginatedBlockedUsers = $userBlockedRepository->getPaginatedBlockedUsers(
            $request->query->getInt('page', 1)
        );

        return $this->render(
            'admin/user/list_blocked_users.html.twig',
            [
                'paginatedUsers' => $paginatedBlockedUsers,
            ]
        );
    }

    /**
     * @Route("/deleted", methods={"GET"}, name="app_admin_deleted_users")
     */
    public function getDeletedUsers(
        Request $request,
        UserRepository $userRepository
    ): Response {
        $paginatedUsers = $userRepository->getPaginatedDeletedUsers(
            $request->query->getInt('page', 1)
        );

        return $this->render(
            'admin/user/list_deleted_users.html.twig',
            [
                'paginatedUsers' => $paginatedUsers,
            ]
        );
    }

    /**
     * @Route("/add-patron/{id}", methods={"GET"}, name="app_admin_add_patron")
     *
     * @IsGranted("ROLE_MASTER")
     */
    public function addPatron(
        Request $request,
        User $member,
        EntityManagerInterface $entityManager
    ): Response {
        $member->setIsPatron(true);
        $entityManager->flush();

        return $this->redirectToRoute(
            'app_admin_get_users',
            $request->query->all()
        );
    }
}
