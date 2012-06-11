<?php

namespace Supra\Form\Constraints;

use Supra\Form\Constraints\Abstraction\Constraint;

/**
 * @Annotation
 *
 * @api
 */
class Choice extends Constraint
{
    public $choices;
    public $callback;
    public $multiple = false;
    public $strict = false;
    public $min = null;
    public $max = null;
    public $message = 'The value you selected is not a valid choice.';
    public $multipleMessage = 'One or more of the given values is invalid.';
    public $minMessage = 'You must select at least {{ limit }} choices.';
    public $maxMessage = 'You must select at most {{ limit }} choices.';

    /**
     * {@inheritDoc}
     */
    public function getDefaultOption()
    {
        return 'choices';
    }
}
