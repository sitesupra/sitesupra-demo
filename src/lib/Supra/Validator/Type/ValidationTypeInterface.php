<?php

namespace Supra\Validator\Type;

/**
 * Interface for validation type classes
 */
interface ValidationTypeInterface
{
	const CN = __CLASS__;
	
	/**
	 * @param string $value
	 * @throws Exception\ValidationFailure
	 */
	public function validate(&$value);
	
	/**
	 * @return boolean
	 */
	public function isValid($value);
}
