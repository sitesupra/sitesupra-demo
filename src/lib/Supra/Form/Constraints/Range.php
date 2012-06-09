<?php

namespace Supra\Form\Constraints;

use Supra\Form\Constraints\Abstraction\Constraint;

/**
 * @Annotation
 *
 * @api
 */
class Range extends Constraint
{

	public $minMessage = 'This value should be {{ limit }} or more.';
	public $maxMessage = 'This value should be {{ limit }} or less.';
	public $invalidMessage = 'This value should be a valid number.';
	public $min;
	public $max;

	/**
	 * {@inheritDoc}
	 */
	public function getRequiredOptions()
	{
		return array('min', 'max');
	}

}
