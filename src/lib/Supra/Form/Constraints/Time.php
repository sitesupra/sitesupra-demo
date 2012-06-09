<?php

namespace Supra\Form\Constraints;

use Supra\Form\Constraints\Abstraction\Constraint;

/**
 * @Annotation
 *
 * @api
 */
class Time extends Constraint
{
    public $message = 'This value is not a valid time.';
}
