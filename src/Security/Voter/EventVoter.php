<?php
declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Event;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class EventVoter extends Voter
{
    protected function supports($attribute, $subject)
    {
        return $attribute === 'EVENT_AUTHOR' && $subject instanceof Event;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        /** @var Event $subject */

        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            return false;
        }

        return $subject->getOrganizer() === $user;
    }
}