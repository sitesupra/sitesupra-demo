<?php

namespace Supra\Form\Constraints;

use Supra\Form\Constraints\Abstraction\Constraint;

/**
 * @Annotation
 *
 * @api
 */
class True extends Constraint
{
    public $message = 'This value should be true.';
}
