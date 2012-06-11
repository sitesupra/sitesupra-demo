<?php

namespace Supra\Form\Constraints;

use Supra\Form\Constraints\Abstraction\Constraint;

/**
 * @Annotation
 *
 * @api
 */
class Locale extends Constraint
{
    public $message = 'This value is not a valid locale.';
}
