<?php

namespace Supra\Form\Constraints;

use Supra\Form\Constraints\Abstraction\Constraint;

/**
 * @Annotation
 *
 * @api
 */
class NotBlank extends Constraint
{
    public $message = 'This value should not be blank.';
}
