<?php

namespace Supra\Validator;

/**
 * Filtered array
 */
class FilteredInput extends \ArrayIterator
{
	/**
	 * Constructor
	 * @param mixed $array array or iterator
	 */
	public function __construct($array = array())
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
		if (is_null($index)) {
			return false;
		}
		
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
		if (is_null($index)) {
			return false;
		}
		
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
	public function getChild($index, $emptyAsDefault = false)
	{
		if ($this->hasChild($index)) {
			$value = $this->offsetGet($index);
			$value = new static($value);
			
			return $value;
		} elseif ($emptyAsDefault) {
			return new static();
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
	 * If the next value is an array
	 * @return boolean
	 */
	public function hasNextChild()
	{
		$key = $this->key();
		$has = $this->hasChild($key);
		
		return $has;
	}
	
	/**
	 * If the next value is scalar
	 * @return boolean
	 */
	public function hasNext()
	{
		$key = $this->key();
		$has = $this->has($key);
		
		return $has;
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
	 * @param mixed $additionalParameters
	 * @return mixed
	 * @throws Exception\ValidationFailure
	 */
	public static function validate($value, $type, $additionalParameters = null)
	{
		$validator = Type\AbstractType::getType($type);
		$validator->validate($value, $additionalParameters);
		
		return $value;
	}
	
	/**
	 * Get validated (and sanitized) value
	 * @param mixed $index
	 * @param string $type
	 * @param mixed $additionalParameters
	 * @return mixed
	 * @throws Exception\ValidationFailure
	 */
	public function getValid($index, $type, $additionalParameters = null)
	{
		$value = $this->get($index);
		$validValue = self::validate($value, $type, $additionalParameters);
		
		return $validValue;
	}
	
	/**
	 * Return valid value or null if offset doesn't exist, throws exception if
	 * not valid.
	 * @param string $index
	 * @param string $type
	 * @param mixed $additionalParameters
	 * @return mixed
	 * @throws Exception\ValidationFailure
	 */
	public function getValidIfExists($index, $type, $additionalParameters = null)
	{
		$valid = null;
		
		if ( ! $this->has($index)) {
			return null;
		}
		
		$value = $this->getValid($index, $type, $additionalParameters);
		
		return $value;
	}
	
	/**
	 * Return valid value or null if offset doesn't exist or is not valid
	 * @param string $index
	 * @param string $type
	 * @param mixed $additionalParameters
	 * @return mixed
	 */
	public function getValidOrNull($index, $type, $additionalParameters = null)
	{
		
		if ( ! $this->has($index)) {
			return null;
		}
		
		$value = $this->get($index);
		
		$validator = Type\AbstractType::getType($type);

		$validator->sanitize($value, $additionalParameters);
		
		return $value;
	}
	
	/**
	 * Get validated (and sanitized) next value
	 * @param string $type
	 * @param mixed $additionalParameters
	 * @return mixed
	 */
	public function getNextValid($type, $additionalParameters = null)
	{
		$value = $this->getNext();
		$validValue = self::validate($value, $type, $additionalParameters);
		
		return $validValue;
	}
	
	/**
	 * Check if the offset value is valid
	 * @param mixed $index
	 * @param string $type
	 * @return mixed
	 */
	public function isValid($index, $type, $additionalParameters = null)
	{
		if ( ! $this->has($index)) {
			return false;
		}
		
		$value = $this->get($index);
		
		$validator = Type\AbstractType::getType($type);
		$valid = $validator->isValid($value, $additionalParameters);
		
		return $valid;
	}
}
