<?php

namespace Supra\ObjectRepository;

/**
 * Object repository
 *
 */
class ObjectRepository {
	
	/**
	 * Object relation storage
	 *
	 * @var array
	 */
	protected static $objectBindings = array();

	/**
	 * Get object of specified interface assigned to caller class
	 *
	 * @param object/string $callerClass
	 * @param string $interfaceName
	 * @return object
	 */
	public static function getObject($caller, $interfaceName)
	{
		if (is_object($caller)) {
			$caller = get_class($caller);
		} else if ( ! is_string($caller)) {
			throw new \RuntimeException('Caller must be class instance or class name');
		}
		$objects = self::$objectBindings['!default'];
		if (isset(self::$objectBindings[$caller])) {
			$objects = array_merge($objects, self::$objectBindings[$caller]);
		}

		if (isset($objects[$interfaceName])) {
			return $objects[$interfaceName];
		} else {
			return null;
		}
	}

	/**
	 * Assign object of its own class to caller class
	 *
	 * @param object/string $caller
	 * @param object $object 
	 */
	public static function setObject($caller, $object)
	{
		self::addBinding($caller, $object);
	}

	/**
	 * Set default assigned object of its class
	 *
	 * @param type $object 
	 */
	public static function setDefaultObject($object)
	{
		self::addBinding('!default', $object);
	}

	/**
	 * Get assigned logger
	 *
	 * @param string/object $caller
	 * @return object
	 */
	public static function getLogger($caller)
	{
		return self::getObject($caller, '!Logger');
	}

	/**
	 * Assign logger instance to caller class
	 *
	 * @param string/object $caller
	 * @param object $object 
	 */
	public static function setLogger($caller, $object) {
		self::addBinding($caller, $object, '!Logger');
	}

	/**
	 * Set default logger
	 *
	 * @param object $object 
	 */
	public static function setDefaultLogger($object)
	{
		self::addBinding('!default', $object, '!Logger');
	}

	/**
	 * Get entity manager assigned to caller class
	 *
	 * @param string/object $caller
	 * @return object
	 */
	public static function getEntityManager($caller)
	{
		return self::getObject($caller, '!EntityManager');
	}

	/**
	 * Assign entity manager instance to caller class
	 *
	 * @param object/string $caller
	 * @param object $object 
	 */
	public static function setEntityManager($caller, $object)
	{
		self::addBinding($caller, $object, '!EntityManager');
	}

	/**
	 * Set default entity manager
	 *
	 * @param type $object 
	 */
	public static function setDefaultEntityManager($object)
	{
		self::addBinding('!default', $object, '!EntityManager');
	}


	/**
	 * Get assigned file storage
	 *
	 * @param string/object $caller
	 * @return object
	 */
	public static function getFileStorage($caller)
	{
		return self::getObject($caller, '!FileStorage');
	}

	/**
	 * Assign file storage instance to caller class
	 *
	 * @param string/object $caller
	 * @param object $object 
	 */
	public static function setFileStorage($caller, $object) {
		self::addBinding($caller, $object, '!FileStorage');
	}

	/**
	 * Set default file storage
	 *
	 * @param object $object 
	 */
	public static function setDefaultFileStorage($object)
	{
		self::addBinding('!default', $object, '!FileStorage');
	}

	/**
	 * Internal relation setter
	 *
	 * @param string $callerClass
	 * @param object $object
	 * @param string $interfaceClass 
	 */
	protected static function addBinding($caller, $object, $interfaceClass = null)
	{
		if (is_object($caller)) {
			$caller = get_class($caller);
		} else if ( ! is_string($caller)) {
			throw new \RuntimeException('Caller must be class instance or class name');
		}
		if ( ! is_object($object)) {
			throw new \RuntimeException('Object must be an object');
		}
		if (is_null($interfaceClass)) {
			$interfaceClass = get_class($object);
		}
		
		self::$objectBindings[$caller][$interfaceClass] = $object;
	}	
	
}
