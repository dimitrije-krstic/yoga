<?php
declare(strict_types=1);

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

class SocialNetworkController extends AbstractController
{
    /**
     * Link to this controller to start the "connect" process
     *
     * @Route("/connect/facebook", name="connect_facebook_start")
     */
    public function connectFacebookAction(ClientRegistry $clientRegistry, string $env): RedirectResponse
    {
        $options = $env === 'prod' ?
            [] : ['redirect_uri' => 'https://thomasmcdonald.github.io/Localhost-uri-Redirector/'];

        return $clientRegistry
            ->getClient('facebook')
            ->redirect(
                ['public_profile', 'email'],
                $options
            );
    }

    /**
     * After going to Facebook, you're redirected back here
     * because this is the "redirect_route" you configured
     * in config/packages/knpu_oauth2_client.yaml

     * @Route("/connect/facebook/check", name="connect_facebook_check")
     */
    public function connectFacebookCheckAction(): RedirectResponse
    {
        return $this->redirectToRoute('app_user_account');
    }

    /**
     * Link to this controller to start the "connect" process
     *
     * @Route("/connect/google", name="connect_google_start")
     */
    public function connectGoogleAction(ClientRegistry $clientRegistry, string $env): RedirectResponse
    {
        $options = $env === 'prod' ?
            [] : ['redirect_uri' => 'https://thomasmcdonald.github.io/Localhost-uri-Redirector/'];

        return $clientRegistry
            ->getClient('google')
            ->redirect(
                ['profile', 'email'],
                $options
            );
    }

    /**
     * After going to Google, you're redirected back here
     * because this is the "redirect_route" you configured
     * in config/packages/knpu_oauth2_client.yaml

     * @Route("/connect/google/check", name="connect_google_check")
     */
    public function connectGoogleCheckAction(): RedirectResponse
    {
        return $this->redirectToRoute('app_user_account');
    }
}