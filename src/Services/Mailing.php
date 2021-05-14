<?php
declare(strict_types=1);

namespace App\Services;

use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

class Mailing
{
    private const APP_EMAIL_FROM_NAME = 'WeAreYogis';
    private const APP_EMAIL_FROM = 'no-reply@weareyogis.net';
    private const APP_EMAIL_TO_NAME = 'Contact WeAreYogis';
    private const APP_EMAIL_TO = 'contact@weareyogis.net';

    private const WELCOME_TEMPLATE = 'email/welcome.html.twig';
    private const WELCOME_TEMPLATE_VERIFIED = 'email/welcome_verified.html.twig';
    private const WELCOME_SUBJECT = 'Namaste and Welcome to WeAreYogis';
    private const RESET_TEMPLATE = 'email/reset_password.html.twig';
    private const RESET_SUBJECT = 'Your password reset request';
    private const EMAIL_VERIFY_TEMPLATE = 'email/verify_email.html.twig';
    private const EMAIL_VERIFY_SUBJECT = 'Your email verify link request';
    private const CONTACT_US_TEMPLATE = 'email/contact_us.html.twig';
    private const CONTACT_US_SUBJECT = 'Contact Us: ';

    private $mailer;
    private $resetPasswordHelper;
    private $verifyEmailHelper;

    public function __construct(
        MailerInterface $mailer,
        ResetPasswordHelperInterface $resetPasswordHelper,
        VerifyEmailHelperInterface $verifyEmailHelper
    )
    {
        $this->mailer = $mailer;
        $this->resetPasswordHelper = $resetPasswordHelper;
        $this->verifyEmailHelper = $verifyEmailHelper;
    }

    public function sendContactUsEmail(
        string $name,
        string $email,
        string $subject,
        string $content
    ): void {
        $message = (new TemplatedEmail())
            ->from(new Address(self::APP_EMAIL_FROM, self::APP_EMAIL_FROM_NAME))
            ->to(new Address(self::APP_EMAIL_TO, self::APP_EMAIL_TO_NAME))
            ->subject(self::CONTACT_US_SUBJECT . $subject)
            ->htmlTemplate(self::CONTACT_US_TEMPLATE)
            ->context([
                'content' => $content,
                'subject' => $subject,
                'userEmail' => $email,
                'userName' => $name
            ]);

        $this->mailer->send($message);
    }

    public function sendWelcomeEmailVerify(User $user, array $context = []): void
    {
        $this->sendEmailWithVerificationLink(
            $user,
            $context,
            self::WELCOME_SUBJECT,
            self::WELCOME_TEMPLATE
        );
    }

    public function sendWelcomeEmailWithoutVerifyLink(User $user, array $context = []): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address(self::APP_EMAIL_FROM, self::APP_EMAIL_FROM_NAME))
            ->to(new Address($user->getEmail(), $user->getName()))
            ->subject(self::WELCOME_SUBJECT)
            ->htmlTemplate(self::WELCOME_TEMPLATE_VERIFIED)
            ->context($context);

        $this->mailer->send($email);
    }

    public function sendEmailVerify(User $user, array $context = []): void
    {
        $this->sendEmailWithVerificationLink(
            $user,
            $context,
            self::EMAIL_VERIFY_SUBJECT,
            self::EMAIL_VERIFY_TEMPLATE
        );
    }

    public function sendResetPassword(User $user, array $context = []): bool
    {
        try {
            $resetToken = $this->resetPasswordHelper->generateResetToken($user);
        } catch (ResetPasswordExceptionInterface $e) {
            return false;
        }

        $email = (new TemplatedEmail())
            ->from(new Address(self::APP_EMAIL_FROM, self::APP_EMAIL_FROM_NAME))
            ->to($user->getEmail())
            ->subject(self::RESET_SUBJECT)
            ->htmlTemplate(self::RESET_TEMPLATE)
            ->context(array_merge(
                $context,
                [
                    'resetToken' => $resetToken,
                    'tokenLifetime' => $this->resetPasswordHelper->getTokenLifetime(),
                ]
            ))
        ;

        $this->mailer->send($email);

        return true;
    }

    private function sendEmailWithVerificationLink(User $user, array $context, string $subject, string $template): void
    {
        $verifyEmailComponents = $this->verifyEmailHelper->generateSignature(
            'app_user_email_verification',
            (string) $user->getId(),
            $user->getEmail()
        );

        $email = (new TemplatedEmail())
            ->from(new Address(self::APP_EMAIL_FROM, self::APP_EMAIL_FROM_NAME))
            ->to(new Address($user->getEmail(), $user->getName()))
            ->subject($subject)
            ->htmlTemplate($template)
            ->context(array_merge($context, [
                'verifyUrl' => $verifyEmailComponents->getSignedUrl(),
                'verifyUrlExpiresAt' => $verifyEmailComponents->getExpiresAt()->getTimestamp()
            ]));

        $this->mailer->send($email);
    }
}
