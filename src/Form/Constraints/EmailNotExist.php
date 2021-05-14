<?php
declare(strict_types=1);

namespace App\Form\Constraints;

use Symfony\Component\Validator\Constraint;

class EmailNotExist extends Constraint
{
    public $message = 'User with this email is already registered';
}
