<?php

namespace Supra\Form\Constraints;

use Supra\Form\Constraints\Abstraction\Constraint;

/**
 * @Annotation
 *
 * @api
 */
class NotNull extends Constraint
{
    public $message = 'This value should not be null.';
}
