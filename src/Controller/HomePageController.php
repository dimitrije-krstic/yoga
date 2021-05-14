<?php
declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Form\Security\PublicContactType;
use App\Repository\UserRepository;
use App\Services\Mailing;
use App\Services\MessageService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomePageController extends AbstractController
{
    /**
     * @Route("/", methods={"GET"}, name="app_homepage")
     */
    public function getHomepage(UserRepository $userRepository): Response
    {
        if (!$user = $this->getUser()) {
            return $this->render('homepage/public.html.twig');
        }

        return $this->redirectToRoute('app_contribute');
    }

    /**
     * @Route("/contribute", methods={"GET"}, name="app_contribute")
     */
    public function getContributePage(UserRepository $userRepository): Response
    {
        $criteria = ['acceptedTermsOfUse' => true, 'verified' => true];
        if ($this->getUser() === null) {
            $criteria['accountPubliclyVisible'] = true;
        }

        $users = $userRepository->findBy(
            $criteria,
            ['createdAt' => 'DESC'],
            12
        );

        return $this->render(
            'homepage/contribute.html.twig',
            [
                'users' => $users
            ]
        );
    }

    /**
     * @Route("/patron", methods={"GET", "POST"}, name="app_patron")
     */
    public function setPatronMessage(UserRepository $userRepository): Response
    {
        $this->addFlash('success','Thank you for generosity and support! This will help us grow further.');

        return $this->redirectToRoute('app_contribute');
    }

    /**
     * @Route("/about", methods={"GET"}, name="app_about_us")
     */
    public function getAboutPage(): Response
    {
        return $this->render('homepage/about.html.twig');
    }

    /**
     * @Route("/contact", methods={"GET", "POST"}, name="app_contact_us")
     */
    public function getContactPage(
        Request $request,
        Mailing $mailing,
        UserRepository $userRepository,
        MessageService $messageService
    ): Response {
        $form = $this->createForm(PublicContactType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            /** @var User|null $user */
            $user = $this->getUser();
            if ($user && $user->isVerified()) {
                $messageService->createNewDirectMessageThread(
                    $user,
                    $userRepository->getMasterAdminUser(),
                    'Contact us: ' . $data['subject'],
                    $data['content']
                );
            } else {
                $mailing->sendContactUsEmail(
                    $data['name'],
                    $data['email'],
                    $data['subject'],
                    $data['content']
                );
            }

            $this->addFlash('success', 'Thank you. We have received your message.');

            return $this->redirectToRoute('app_contact_us');
        }

        return $this->render(
            'homepage/contact.html.twig',
            [
                'form' => $form->createView()
            ]
        );
    }
}
