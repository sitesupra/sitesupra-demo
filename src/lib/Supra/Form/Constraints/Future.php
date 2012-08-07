<?php

namespace Supra\Form\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Adds check for future date
 * @Annotation
 */
class Future extends Constraint
{
	public $message = 'Date must be in future';
}
