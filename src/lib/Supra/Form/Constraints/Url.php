<?php

namespace Supra\Form\Constraints;

use Supra\Form\Constraints\Abstraction\Constraint;

/**
 * @Annotation
 *
 * @api
 */
class Url extends Constraint
{
    public $message = 'This value is not a valid URL.';
    public $protocols = array('http', 'https');
}
