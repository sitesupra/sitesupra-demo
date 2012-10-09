<?php

namespace Supra\Form;

use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Constraint;

/**
 * Empty validator
 */
class FormFieldValidator extends ConstraintValidator
{
	public function validate($value, Constraint $constraint)
	{
		
	}
}
