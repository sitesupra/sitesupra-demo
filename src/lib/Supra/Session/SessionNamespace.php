<?php

namespace Supra\Session;

/*
 * Session namespace
 */

class SessionNamespace
{

	/**
	 * Name of namespace
	 * 
	 * @var string
	 */
	protected $__name;

	/**
	 * @var boolean
	 */
	protected $__closed;

	/**
	 * @var boolean
	 */
	protected $__dirty;

	/**
	 * @var mixed
	 */
	protected $__data;

	/**
	 * Construct and initialize.
	 * 
	 * @param string $name
	 */
	function __construct($name = null)
	{
		$this->__dirty = false;
		$this->__data = array();
		$this->__closed = false;
		$this->__name = $name;
	}

	/**
	 * @param string $name
	 */
	public function setName($name)
	{
		$this->__name = $name;
	}

	/**
	 * After wakeup session namespace is neither dirty, nor closed.
	 */
	public function __wakeup()
	{
		$this->__dirty = false;
		$this->__closed = false;
	}

	/**
	 * Return session namespace name
	 * 
	 * @return string
	 */
	public function getName()
	{
		return $this->__name;
	}

	/**
	 * Return if session is dirty, e.i. if has been modified since initialization.
	 * 
	 * @return boolean
	 */
	public function isDirty()
	{
		return $this->__dirty;
	}

	/**
	 * Sets value to a key in session namespace.
	 * 
	 * @param type $key
	 * @param type $value 
	 */
	public function __set($key, $value)
	{
		if ($this->__closed) {
			throw new Exception\ClosedSessionNamespaceAccess();
		}

		$this->__dirty = true;
		$this->__data[$key] = $value;
	}

	/**
	 * Returns a value of a key from session namespace, or default value if there is no such key.
	 * 
	 * @param string $key
	 * @param mixed $defaultValue
	 * @return mixed 
	 */
	public function __get($key)
	{
		if ($this->__closed) {
			throw new Exception\ClosedSessionNamespaceAccess();
		}

		return isset($this->__data[$key]) ? $this->__data[$key] : null;
	}

	/**
	 * Unsets key in session namespace.
	 * 
	 * @param string $key 
	 */
	public function __unset($key)
	{
		if ($this->__closed) {
			throw new Exception\ClosedSessionNamespaceAccess();
		}

		$this->__dirty = true;

		unset($this->__data[$key]);
	}

	/**
	 * Checks if a key exists (is set) in session namespace.
	 * 
	 * @param string $key
	 * @return boolean
	 */
	public function __isset($key)
	{
		if ($this->__closed) {
			throw new Exception\ClosedSessionNamespaceAccess();
		}

		return isset($this->__data[$key]);
	}

	/**
	 * Gets a value from session namespace and unsets it, if it exists. 
	 * Returns default value if no such key exists in session namespace.
	 * 
	 * @param string $key
	 * @param mixed $defaultValue
	 * @return mixed
	 */

	/**
	 * Clears all data from session namespace
	 */
	public function clear()
	{
		if ($this->__closed) {
			throw new Exception\ClosedSessionNamespaceAccess();
		}

		$this->__dirty = true;

		$this->__data = array();
	}

	/**
	 * Marks namespace as closed. Any attempt to modify session after 
	 * this will rise an Exception\ClosedSessionNamespaceAccess.
	 */
	public function close()
	{
		if ($this->__closed) {
			throw new Exception\ClosedSessionNamespaceAccess();
		}

		$this->__closed = true;

		//\Log::error('SESSION NAMESPACE CLOSE ' . $this->getName() . ': ', $this->__data);
	}

}
