<?php

namespace Supra\Form\Constraints;

use Supra\Form\Constraints\Abstraction\Constraint;

/**
 * @Annotation
 *
 * @api
 */
class Type extends Constraint
{
    public $message = 'This value should be of type {{ type }}.';
    public $type;

    /**
     * {@inheritDoc}
     */
    public function getDefaultOption()
    {
        return 'type';
    }

    /**
     * {@inheritDoc}
     */
    public function getRequiredOptions()
    {
        return array('type');
    }
}
