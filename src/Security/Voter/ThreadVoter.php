<?php
declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\MessageThread;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class ThreadVoter extends Voter
{
    protected function supports($attribute, $subject)
    {
        return $attribute === 'THREAD_AUTHOR' && $subject instanceof MessageThread;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        /** @var MessageThread $subject */

        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            return false;
        }

        return $subject->getCreatedBy() === $user;
    }
}