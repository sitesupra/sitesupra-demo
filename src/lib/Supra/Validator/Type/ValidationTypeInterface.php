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
	public function validate(&$value/*, $additionalParameters = null*/);
	
	/**
	 * @param mixed $additionalParameters
	 * @return boolean
	 */
	public function isValid($value, $additionalParameters = null);
	
	/**
	 * Sanitize the value, nullify if is not valid
	 * @param mixed $value
	 * @param mixed $additionalParameters
	 * @return boolean
	 */
	public function sanitize(&$value, $additionalParameters = null);
}
