<?php

namespace Supra\Validator;

/**
 * Filtered array
 */
class FilteredInput extends \ArrayIterator
{
	/**
	 * Constructor
	 * @param array $array
	 */
	public function __construct(array $array = array())
	{
		parent::__construct($array);
	}
	
	/**
	 * Whether the value key exists
	 * @param mixed $index
	 * @return boolean 
	 */
	public function has($index)
	{
		if ( ! $this->offsetExists($index)) {
			return false;
		}
		
		$value = $this->offsetGet($index);
		
		if ( ! is_null($value) && ! is_scalar($value)) {
			return false;
		}
		
		return true;
	}
	
	/**
	 * Whether the subarray input exists
	 * @param mixed $index
	 * @return boolean
	 */
	public function hasChild($index)
	{
		if ( ! $this->offsetExists($index)) {
			return false;
		}
		
		$value = $this->offsetGet($index);
		
		if ( ! is_array($value)) {
			return false;
		}
		
		return true;
	}
	
	/**
	 * Whether such value exists in the array
	 * @param string $value
	 * @param boolean $strict
	 * @return boolean
	 */
	public function contains($value, $strict = false)
	{
		$restore = false;
		$key = null;
		if ($this->valid()) {
			$key = $this->key();
			$restore = true;
		}
		
		$this->rewind();
		$found = false;
		
		while ($this->valid()) {
			
			// Skip arrays
			if ($this->hasChild($this->key())) {
				continue;
			}
			
			$checkValue = $this->getNext();
			
			if ($checkValue === $value || ( ! $strict && $checkValue == $value)) {
				$found = true;
				break;
			}
		}
		
		if ($restore) {
			$this->seek($key);
		}
		
		return $found;
	}
	
	/**
	 * Load scalar value
	 * @param mixed $index
	 * @param string $default
	 * @return string
	 * @throws Exception\RuntimeException
	 */
	public function get($index, $default = null)
	{
		$defaultSet = (func_num_args() > 1);
		
		$value = $default;
		
		if ($this->has($index)) {
			$value = $this->offsetGet($index);
		} elseif ( ! $defaultSet) {
			throw new Exception\RuntimeException("No such offset or does not contain scalar value");
		}
		
		return $value;
	}

	/**
	 * Load subarray filtered input object
	 * @param string $index
	 * @return FilteredInput
	 * @throws Exception\RuntimeException
	 */
	public function getChild($index)
	{
		if ($this->hasChild($index)) {
			$value = $this->offsetGet($index);
			$value = new self($value);
			
			return $value;
		} else {
			throw new Exception\RuntimeException("No such offset or does not contain array");
		}
	}
	
	/**
	 * Loads next value in scalar value input and advances the iterator pointer.
	 * NB! Will raise exception if array is found.
	 * @return string
	 * @throws \OutOfBoundsException
	 */
	public function getNext()
	{
		if ( ! $this->valid()) {
			throw new \OutOfBoundsException("End of iterator reached");
		}
		
		$key = $this->key();
		$value = $this->get($key);
		
		$this->next();
		
		return $value;
	}
	
	/**
	 * Loads next child input and advances the iterator pointer.
	 * NB! Will raise exception if scalar value is found.
	 * @return FilteredInput
	 * @throws \OutOfBoundsException
	 */
	public function getNextChild()
	{
		if ( ! $this->valid()) {
			throw new \OutOfBoundsException("End of iterator reached");
		}
		
		$key = $this->key();
		$value = $this->getChild($key);
		
		$this->next();
		
		return $value;
	}
	
	/**
	 * Whether the value in the index is empty.
	 * "0" is treated empty only if $strict is off.
	 * NB! empty array is not treated as empty, exception will be raised.
	 * @param string $index
	 * @param boolean $strict
	 * @return boolean
	 */
	public function isEmpty($index, $strict = true)
	{
		$value = $this->get($index, null);
		$empty = null;
		
		if ($strict) {
			$empty = ($value == '');
		} else {
			$empty = empty($value);
		}
		
		return $empty;
	}
	
	/**
	 * Static validation method
	 * @param string $value
	 * @param string $type
	 * @param array $additionalParameters
	 * @return mixed
	 */
	public static function validate($value, $type, array $additionalParameters = array())
	{
		$validator = Type\AbstractType::getType($type);
		
		// Mainly done for "find usages" feature
		if (empty($additionalParameters)) {
			$validator->validate($value);
		} else {
			call_user_func_array(array($validator, 'validate'), $additionalParameters);
		}
		
		return $value;
	}
	
	/**
	 * Get validated (and sanitized) value
	 * @param mixed $index
	 * @param string $type
	 * @param mixed $additionalParameter
	 * @param mixed $additionalParameter2
	 * @return mixed
	 */
	public function getValid($index, $type, $additionalParameter = null, $additionalParameter2 = null)
	{
		$value = $this->get($index);
		$additionalParameters = array_slice(func_get_args(), 2);
		$validValue = self::validate($value, $type, $additionalParameters);
		
		return $validValue;
	}
	
	/**
	 * Get validated (and sanitized) next value
	 * @param string $type
	 * @param mixed $additionalParameter
	 * @param mixed $additionalParameter2
	 * @return mixed
	 */
	public function getNextValid($type, $additionalParameter = null, $additionalParameter2 = null)
	{
		$value = $this->getNext();
		$additionalParameters = array_slice(func_get_args(), 1);
		$validValue = self::validate($value, $type, $additionalParameters);
		
		return $validValue;
	}
	
	/**
	 * Check if the offset value is valid
	 * @param mixed $index
	 * @param string $type
	 * @return mixed
	 */
	public function isValid($index, $type, $additionalParameter = null, $additionalParameter2 = null)
	{
		$value = $this->get($index);
		$additionalParameters = array_slice(func_get_args(), 2);
		
		$validator = Type\AbstractType::getType($type);
		$valid = null;
		
		// Mainly done for "find usages" feature
		if (empty($additionalParameters)) {
			$valid = $validator->isValid($value);
		} else {
			$valid = call_user_func_array(array($validator, 'isValid'), $additionalParameters);
		}
		
		return $valid;
	}
}
