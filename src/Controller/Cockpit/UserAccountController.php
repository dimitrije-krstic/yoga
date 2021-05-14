<?php
declare(strict_types=1);

namespace App\Controller\Cockpit;

use App\Entity\User;
use App\Form\Cockpit\UserAccount\UserType;
use App\Services\Mailing;
use App\Services\UploadHelper;
use App\Services\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/cockpit/account")
 */
class UserAccountController extends AbstractController
{
    /**
     * @Route("", methods={"GET", "POST"}, name="app_user_account")
     */
    public function getUserAccount(
        Request $request,
        EntityManagerInterface $entityManager,
        UploadHelper $uploadHelper,
        Mailing $mailing
    ): Response {
        /** @var User $user */
        $user =  $this->getUser();

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($newFile = $form->get('photo')->getData()) {
                $uploadHelper->uploadUserProfileImage($newFile, $user);
            }

            if ($newEmail = $form->get('newEmail')->getData()) {
                $user->setEmail(\mb_strtolower(\trim($newEmail)));
                $user->setVerified(false);
                $mailing->sendEmailVerify($user);
                $this->addFlash('success','Verification link for your new email has been sent.');
            }

            $entityManager->flush();
            $this->addFlash('success','You have updated your profile.');

            return $this->redirectToRoute('app_user_account');
        }

        return $this->render('user/account.html.twig',[
                'form' => $form->createView()
            ]
        );
    }

    /**
     * //TODO change it to DELETE
     * @Route("/image/delete", methods={"GET"}, name="app_user_account_delete_profile_image")
     */
    public function deleteUserProfileImage(
        EntityManagerInterface  $entityManager,
        UploadHelper $uploadHelper
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if (($image = $user->getProfileImage()) !== null) {
            $user->setProfileImage(null);
            $uploadHelper->deleteUserProfileImage($image);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_user_account');
    }

    /**
     * //TODO change it to DELETE
     * @Route("/delete", methods={"GET"}, name="app_user_account_delete")
     */
    public function deleteUserProfile(
        UserService $userService
    ): Response {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            $this->addFlash('error', 'To delete your account you need to login again');
            return $this->redirectToRoute('app_user_account');
        }

        /** @var User $user */
        $user = $this->getUser();
        $userService->softDeleteUserProfile($user);

        return $this->redirectToRoute('app_logout');
    }
}
