<?php

namespace Supra\Request;

/**
 * POST/GET data
 * @TODO: move to common place
 */
class RequestData extends \ArrayIterator
{
	protected static $validators = array();
	
	public function __construct(array $array = array())
	{
		parent::__construct($array);
	}
	
	public static function registerValidator($type, $validator)
	{
		self::$validators[$type] = $validator;
	}
	
	public static function getValidator($type)
	{
		throw new \InvalidArgumentException("Validator not found for type $type");
	}
	
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
	
	public function hasArray($index)
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
	
	public function get($index, $default = null)
	{
		$defaultSet = (func_num_args() > 1);
		
		$value = $default;
		
		if ($this->has($index)) {
			$value = $this->offsetGet($index);
		} elseif ( ! $defaultSet) {
			throw new \RuntimeException("No such offset or does not contain scalar value");
		}
		
		return $value;
	}

	/**
	 * @param string $index
	 * @return RequestData
	 */
	public function getArray($index)
	{
		if ($this->hasArray($index)) {
			$value = $this->offsetGet($index);
			$value = new self($value);
			
			return $value;
		} else {
			throw new \RuntimeException("No such offset or does not contain array");
		}
	}
	
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
	 * @return RequestData
	 */
	public function getNextArray()
	{
		if ( ! $this->valid()) {
			throw new \OutOfBoundsException("End of iterator reached");
		}
		
		$key = $this->key();
		$value = $this->getArray($key);
		
		$this->next();
		
		return $value;
	}
	
	public function getValid($index, $type, $additionalParameter = null, $additionalParameter2 = null)
	{
		$value = $this->get($index);
		
		$validator = self::getValidator($type);
		$validator->validate($value);
		
		return $value;
	}
}
