<?php

namespace Supra\ObjectRepository;

/**
 * Object repository
 *
 */
class ObjectRepository
{
	const DEFAULT_KEY = '!default';
	
	const INTERFACE_LOGGER = 'Supra\Log\Writer\WriterInterface';

	/**
	 * Object relation storage
	 *
	 * @var array
	 */
	protected static $objectBindings = array(
		self::DEFAULT_KEY => array(),
	);

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
		
		$object = self::findObject($caller, $interfaceName);
		if (is_null($object)) {
			$object = self::findObject(self::DEFAULT_KEY, $interfaceName);
		}
		return $object;
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
		self::addBinding(self::DEFAULT_KEY, $object);
	}

	/**
	 * Get assigned logger
	 *
	 * @param string/object $caller
	 * @return \Supra\Log\Writer\WriterInterface
	 */
	public static function getLogger($caller)
	{
		$logger = self::getObject($caller, self::INTERFACE_LOGGER);

		// Create bootstrap logger in case of missing logger
		if (empty($logger)) {
			$logger = \Supra\Log\Log::getBootstrapLogger();
			self::setDefaultLogger($logger);
		}

		return $logger;
	}

	/**
	 * Assign logger instance to caller class
	 *
	 * @param string/object $caller
	 * @param object $object 
	 */
	public static function setLogger($caller, $object)
	{
		self::addBinding($caller, $object, self::INTERFACE_LOGGER);
	}

	/**
	 * Set default logger
	 *
	 * @param object $object 
	 */
	public static function setDefaultLogger($object)
	{
		self::addBinding(self::DEFAULT_KEY, $object, self::INTERFACE_LOGGER);
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
		self::addBinding(self::DEFAULT_KEY, $object, '!EntityManager');
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
	public static function setFileStorage($caller, $object)
	{
		self::addBinding($caller, $object, '!FileStorage');
	}

	/**
	 * Set default file storage
	 *
	 * @param object $object 
	 */
	public static function setDefaultFileStorage($object)
	{
		self::addBinding(self::DEFAULT_KEY, $object, '!FileStorage');
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

	/**
	 * Find object
	 *
	 * @param string $callerClass
	 * @param string $objectClass 
	 */
	protected static function findObject($callerClass, $objectClass)
	{
		if (empty($callerClass) || empty($objectClass)) {
			return null;
		}

		if (isset(self::$objectBindings[$callerClass], self::$objectBindings[$callerClass][$objectClass])) {
			return self::$objectBindings[$callerClass][$objectClass];
			
		} else if ($callerClass != self::DEFAULT_KEY) {
			$seniorClass = mb_substr($callerClass, 0, mb_strrpos($callerClass, "\\"));
			return self::findObject($seniorClass, $objectClass);
			
		} else {
			return null;
		}
	}
}
