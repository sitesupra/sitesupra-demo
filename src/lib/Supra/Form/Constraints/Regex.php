<?php

namespace Supra\Form\Constraints;

use Supra\Form\Constraints\Abstraction\Constraint;

/**
 * @Annotation
 *
 * @api
 */
class Regex extends Constraint
{

	public $message = 'This value is not valid.';
	public $pattern;
	public $match = true;

	/**
	 * {@inheritDoc}
	 */
	public function getDefaultOption()
	{
		return 'pattern';
	}

	/**
	 * {@inheritDoc}
	 */
	public function getRequiredOptions()
	{
		return array('pattern');
	}

}
