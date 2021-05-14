<?php
declare(strict_types=1);

namespace App\Form\Constraints;

use App\Repository\UserRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class EmailNotExistValidator extends ConstraintValidator
{
    private $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function validate($email, Constraint $constraint): void
    {
        if (!$constraint instanceof EmailNotExist) {
            throw new UnexpectedTypeException($constraint, EmailNotExist::class);
        }

        if (empty($email)) {
            return;
        }

        if ($this->userRepository->findOneBy(['email' => $email])) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
