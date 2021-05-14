<?php
declare(strict_types=1);

namespace App\Form\Constraints;

use App\Repository\BlockedUserRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class EmailNotBlockedValidator extends ConstraintValidator
{
    private $blockedUserRepository;

    public function __construct(BlockedUserRepository $blockedUserRepository)
    {
        $this->blockedUserRepository = $blockedUserRepository;
    }

    public function validate($email, Constraint $constraint): void
    {
        if (!$constraint instanceof EmailNotBlocked) {
            throw new UnexpectedTypeException($constraint, EmailNotBlocked::class);
        }

        if (empty($email)) {
            return;
        }

        if ($this->blockedUserRepository->findOneBy(['email' => $email])) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
