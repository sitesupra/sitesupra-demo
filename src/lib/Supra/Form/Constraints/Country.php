<?php

namespace Supra\Form\Constraints;

use Supra\Form\Constraints\Abstraction\Constraint;

/**
 * @Annotation
 *
 * @api
 */
class Country extends Constraint
{
    public $message = 'This value is not a valid country.';
}
