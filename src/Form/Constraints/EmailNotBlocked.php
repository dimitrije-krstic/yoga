<?php
declare(strict_types=1);

namespace App\Form\Constraints;

use Symfony\Component\Validator\Constraint;

class EmailNotBlocked extends Constraint
{
    public $message = 'This email has been already used for registration';
}
