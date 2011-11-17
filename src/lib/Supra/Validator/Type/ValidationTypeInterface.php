<?php

namespace Supra\Validator\Type;

/**
 * Interface for validation type classes
 */
interface ValidationTypeInterface
{
	/**
	 * @param string $value
	 */
	public function validate(&$value);
}
