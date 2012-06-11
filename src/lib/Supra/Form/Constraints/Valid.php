<?php

namespace Supra\Form\Constraints;

use Supra\Form\Constraints\Abstraction\Constraint;

/**
 * @Annotation
 *
 * @api
 */
class Valid extends Constraint
{
    public $traverse = true;

    public $deep = false;
}
