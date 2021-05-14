<?php
declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use App\Repository\BlockedUserRepository;
use App\Repository\UserRepository;
use App\Services\Mailing;
use App\Services\UploadHelper;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Security\Authenticator\SocialAuthenticator;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Provider\GoogleUser;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class GoogleAuthenticator extends SocialAuthenticator
{
    use TargetPathTrait;

    private ClientRegistry $clientRegistry;
    private EntityManagerInterface $em;
    private UserRepository $userRepository;
    private RouterInterface $router;
    private LoggerInterface $logger;
    private string $env;
    private UploadHelper $uploadHelper;
    private Mailing $mailing;
    private BlockedUserRepository $blockedUserRepository;

    public function __construct(
        ClientRegistry $clientRegistry,
        EntityManagerInterface $em,
        UserRepository $userRepository,
        RouterInterface $router,
        LoggerInterface $logger,
        UploadHelper $uploadHelper,
        Mailing $mailing,
        BlockedUserRepository $blockedUserRepository,
        string $env
    ) {
        $this->clientRegistry = $clientRegistry;
        $this->em = $em;
        $this->userRepository = $userRepository;
        $this->router = $router;
        $this->logger = $logger;
        $this->uploadHelper = $uploadHelper;
        $this->mailing = $mailing;
        $this->env = $env;
        $this->blockedUserRepository = $blockedUserRepository;
    }

    /**
     * @param Request $request
     * @return bool
     */
    public function supports(Request $request)
    {
        return $request->attributes->get('_route') === 'connect_google_check';
    }

    /**
     * @param Request $request
     * @return AccessToken|mixed
     */
    public function getCredentials(Request $request)
    {
        $options = $this->env === 'prod' ?
            [] : ['redirect_uri' => 'https://thomasmcdonald.github.io/Localhost-uri-Redirector/'];

        return $this->fetchAccessToken(
            $this->clientRegistry->getClient('google'),
            $options
        );
    }

    /**
     * @param mixed $credentials
     * @param UserProviderInterface $userProvider
     * @return User|null|object|\Symfony\Component\Security\Core\User\UserInterface
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        /** @var GoogleUser $googleUser */
        $googleUser = $this->clientRegistry->getClient('google')
            ->fetchUserFromToken($credentials);

        if ($this->blockedUserRepository->findOneBy(['email' => $email = \mb_strtolower($googleUser->getEmail())])) {
            return null;
        }

        // 1) have they logged in with Google before?
        $existingUser = $this->userRepository
            ->findOneBy(['googleId' => $googleUser->getId()]);

        if ($existingUser) {
            $user = $existingUser;
        } else {
            // 2) do we have a matching user by email?
            /** @var  AccessToken $credentials */
            $user = $this->userRepository
                ->findOneBy(['email' => $email]);

            if (!$user) {
                $user = new User(
                    $email,
                    $googleUser->getName(),
                    true
                );

                if ($avatarUrl = $googleUser->getAvatar()) {
                    $profileImage = $this->uploadHelper->uploadUserProfileImageFromSocialNetwork(
                        str_replace('s96-c', 's200-c', $avatarUrl)
                    );
                    $user->setProfileImage($profileImage);
                }

                $this->em->persist($user);
                //$this->mailing->sendWelcomeEmailWithoutVerifyLink($user);
            }

            $user->setVerified(true);
            $user->setGoogleId($googleUser->getId());
            $this->em->flush();
        }

        return $user;
    }

    /**
     * @param Request $request
     * @param TokenInterface $token
     * @param string $providerKey
     * @return null|Response
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        $request->request->set('_remember_me', '1');

        if ($targetPath = $this->getTargetPath($request->getSession(), $providerKey)) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->router->generate('app_homepage'));
    }

    /**
     * @param Request $request
     * @param AuthenticationException $exception
     * @return null|Response
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        if ($request->hasSession()) {
            $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);
        }

        $this->logger->error('Google AuthError: '. $exception->getMessage());

        return new RedirectResponse(
            $this->getLoginUrl()
        );
    }

    /**
     * Called when authentication is needed, but it's not sent.
     * This redirects to the 'login'.
     *
     * @param Request $request
     * @param AuthenticationException|null $authException
     *
     * @return RedirectResponse
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        return new RedirectResponse(
            $this->getLoginUrl()
        );
    }

    public function getLoginUrl(): string
    {
        return $this->router->generate('app_login');
    }
}