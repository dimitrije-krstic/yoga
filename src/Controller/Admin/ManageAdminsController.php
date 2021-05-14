<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\Admin\AdminCreationType;
use App\Repository\UserRepository;
use App\Services\Mailing;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin")
 */
class ManageAdminsController extends AbstractController
{
    /**
     * @Route("/list-admins", methods={"GET"}, name="app_admin_list_admins")
     */
    public function listAdmins(UserRepository $userRepository): Response
    {
        $admins = $userRepository->findBy(['acceptedTermsOfUse' => 0]);
        foreach ($admins as $key => $admin) {
            if (in_array('ROLE_MASTER', $admin->getRoles(), true)) {
                unset($admins[$key]);
            }
        }

        $createAdminForm = $this->createForm(AdminCreationType::class);

        return $this->render(
            'admin/user/manage_admins.html.twig',
            [
                'admins' => $admins,
                'createAdminForm' => $createAdminForm->createView()
            ]
        );
    }

    /**
     * @Route("/create-admin", methods={"POST"}, name="app_admin_create_admin")
     *
     * @IsGranted("ROLE_MASTER")
     */
    public function createAdmin(
        Request $request,
        EntityManagerInterface $entityManager,
        Mailing $mailing
    ): Response {
        $form = $this->createForm(AdminCreationType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $user = new User(
                $data['email'],
                $data['name'],
                false,
                ['ROLE_ADMIN']
            );

            $user->setPassword('ChangeMe');
            $entityManager->persist($user);
            $entityManager->flush();

            $mailing->sendResetPassword($user);

            $this->addFlash('success', 'An activation email has been sent to new Admin');
        }

        foreach ($form->getErrors(true) as $error) {
            $this->addFlash(
                'error',
                str_replace('User', 'Admin', $error->getMessage())
            );
        }

        return $this->redirectToRoute('app_admin_list_admins');
    }

    /**
     * TODO change to DELETE
     * @Route("/delete-admin/{id}", methods={"GET"}, name="app_admin_delete_admin")
     *
     * @IsGranted("ROLE_MASTER")
     */
    public function deleteAdminUser(
        Request $request,
        User $user,
        EntityManagerInterface $entityManager
    ): Response {
        $entityManager->remove($user);
        $entityManager->flush();

        return $this->redirectToRoute(
            'app_admin_list_admins',
            $request->query->all()
        );
    }
}
