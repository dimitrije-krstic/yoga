<?php
declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserInfo;
use App\Form\Security\ChangePasswordFormType;
use App\Form\Security\RegistrationType;
use App\Form\Security\ResetPasswordRequestFormType;
use App\Repository\UserRepository;
use App\Security\LoginFormAuthenticator;
use App\Services\Mailing;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

class SecurityController extends AbstractController
{
    use ResetPasswordControllerTrait;

    /**
     * @Route("/login", methods={"GET","POST"}, name="app_login")
     */
    public function login(
        AuthenticationUtils $authenticationUtils,
        AuthorizationCheckerInterface $authChecker
    ): Response {
        if ($authChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('app_user_account');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render(
            'security/login.html.twig',
            [
                'error' =>  $error ,
                'lastUsername' => $lastUsername
            ]
        );
    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout(): void
    {
        throw new \Exception('Will be intercepted before getting here');
    }

    /**
     * @Route("/register", methods={"GET","POST"}, name="app_register")
     */
    public function register(
        Request $request,
        UserPasswordEncoderInterface $passwordEncoder,
        AuthorizationCheckerInterface $authChecker,
        GuardAuthenticatorHandler $guardHandler,
        LoginFormAuthenticator $formAuthenticator,
        Mailing $mailing
    ): Response {
        if ($authChecker->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('app_user_account');
        }

        $form = $this->createForm(RegistrationType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $user = new User(
                \mb_strtolower(\trim($data['email'])),
                \trim($data['name']),
                true
            );

            $user->setPassword($passwordEncoder->encodePassword($user, \trim($data['password'])));

            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            $mailing->sendWelcomeEmailVerify($user);
            $this->addFlash('success', 'Welcome! We have sent you a verification email.');

            return $guardHandler->authenticateUserAndHandleSuccess(
                $user,
                $request,
                $formAuthenticator,
                'main'
            );
        }

        return $this->render(
            'security/registration.html.twig',
            [
                'form' => $form->createView()
            ]
        );
    }

    /**
     * @Route("/verify-request", name="app_user_email_verification_request")
     * @IsGranted("ROLE_USER")
     */
    public function verifyUserEmailRequest(Mailing $mailing):Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $mailing->sendEmailVerify($user);

        $this->addFlash('success', 'New verification link has been sent to your email account.');

        return $this->redirectToRoute('app_user_account');
    }

    /**
     * @Route("/verify", name="app_user_email_verification")
     * @IsGranted("ROLE_USER")
     */
    public function verifyUserEmail(
        Request $request,
        VerifyEmailHelperInterface $verifyEmailHelper,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        try {
            $verifyEmailHelper->validateEmailConfirmation(
                $request->getUri(),
                (string) $user->getId(),
                $user->getEmail()
            );
        } catch (VerifyEmailExceptionInterface $e) {
            $this->addFlash('error', $e->getReason());

            return $this->redirectToRoute('app_user_account');
        }

        $user->setVerified(true);
        $entityManager->persist($user);
        $entityManager->flush();

        $this->addFlash('success', 'Your email has been successfully verified.');

        return $this->redirectToRoute('app_user_account');
    }

    /**
     * Display & process form to request a password reset.
     *
     * @Route("/reset-password-request", name="app_password_reset_request")
     */
    public function passwordResetRequest(
        Request $request,
        UserRepository $userRepository,
        Mailing $mailing
    ): Response {
        $form = $this->createForm(ResetPasswordRequestFormType::class);
        $form->handleRequest($request);

        $isRequestSent = false;
        if ($form->isSubmitted() && $form->isValid()) {

            /** @var User|null $user */
            $user = $userRepository->findOneBy([
                'email' => $form->get('email')->getData(),
            ]);

            if ($user) {
                $isRequestSent = $mailing->sendResetPassword($user);
            }

            // Do not reveal whether a user account was found or not.
            $this->addFlash(
                'success',
                sprintf(
                    'An email has been sent to %s with a link to reset the password.',
                    $form->get('email')->getData()
                )
            );
        }

        return $this->render('security/reset_password_request.html.twig', [
            'requestForm' => $form->createView(),
            'isRequestSent' => $isRequestSent
        ]);
    }

    /**
     * Validates and process the reset URL that the user clicked in their email.
     *
     * @Route("/reset-password/reset/{token}", name="app_reset_password")
     */
    public function resetPassword(
        Request $request,
        UserPasswordEncoderInterface $passwordEncoder,
        ResetPasswordHelperInterface $resetPasswordHelper,
        string $token = null
    ): Response {
        if ($token) {
            // We store the token in session and remove it from the URL, to avoid the URL being
            // loaded in a browser and potentially leaking the token to 3rd party JavaScript.
            $this->storeTokenInSession($token);

            return $this->redirectToRoute('app_reset_password');
        }

        $token = $this->getTokenFromSession();
        if (null === $token) {
            throw $this->createNotFoundException('No reset password token found in the URL or in the session.');
        }

        try {
            /** @var User $user */
            $user = $resetPasswordHelper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface $e) {
            $this->addFlash('reset_password_error', sprintf(
                'There was a problem validating your reset request - %s',
                $e->getReason()
            ));

            return $this->redirectToRoute('app_password_reset_request');
        }

        // The token is valid; allow the user to change their password.
        $form = $this->createForm(ChangePasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // A password reset token should be used only once, remove it.
            $resetPasswordHelper->removeResetRequest($token);

            // Encode the plain password, and set it.
            $encodedPassword = $passwordEncoder->encodePassword(
                $user,
                \trim($form->get('plainPassword')->getData())
            );

            $user->setPassword($encodedPassword);
            $user->setVerified(true);
            $this->getDoctrine()->getManager()->flush();

            // The session is cleaned up after the password has been changed.
            $this->cleanSessionAfterReset();

            return $this->redirectToRoute('app_user_account');
        }

        return $this->render('security/reset_password.html.twig', [
            'resetForm' => $form->createView(),
        ]);
    }

    /**
     * @Route(
     *     "/legal/{page}",
     *     name="app_legal_page",
     *     requirements={"page"="terms|privacy|disclaimer"}
     * )
     */
    public function legalAction(string $page): Response
    {
        return $this->render(
            'legal/'.$page.'.html.twig',
        );
    }
}
