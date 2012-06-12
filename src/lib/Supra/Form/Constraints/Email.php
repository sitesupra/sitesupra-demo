<?php

namespace Supra\Form\Constraints;

use Supra\Form\Constraints\Abstraction\Constraint;

/**
 * @Annotation
 *
 * @api
 */
class Email extends Constraint
{
    public $message = 'This value is not a valid email address.';
    public $checkMX = false;
    public $checkHost = false;
}
