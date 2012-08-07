<?php

namespace Supra\Form\Constraints;

use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Checks if the date is in the future (no time check)
 */
class FutureValidator extends ConstraintValidator
{
	public function validate($value, Constraint $constraint)
	{
		if (is_null($value)) {
			return null;
		}

		if ( ! $value instanceof \DateTime) {
			throw new UnexpectedTypeException($value, '\DateTime');
		}

		/* @var $value \DateTime */

		if ($value->format('Y-m-d') < date('Y-m-d')) {
			$this->context->addViolation($constraint->message);
		}
	}

}
