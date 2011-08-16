<?php

namespace Supra\ObjectRepository;

/**
 * Object repository
 *
 */
class ObjectRepository
{
	const DEFAULT_KEY = '';
	
	const INTERFACE_LOGGER = 'Supra\Log\Writer\WriterInterface';
	const INTERFACE_FILE_STORAGE = 'Supra\FileStorage\FileStorage';
	const INTERFACE_ENTITY_MANAGER = 'Doctrine\ORM\EntityManager';

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

		$caller = trim($caller, "\\");
		$interfaceClass = trim($interfaceClass, "\\");
		
		$object = self::findObject($caller, $interfaceName);

		return $object;
	}

	/**
	 * Assign object of its own class to caller class
	 *
	 * @param object/string $caller
	 * @param object $object 
	 * @param string $interfaceName
	 */
	public static function setObject($caller, $object, $interfaceName)
	{
		self::addBinding($caller, $object, $interfaceName);
	}

	/**
	 * Set default assigned object of its class
	 *
	 * @param type $object 
	 * @param string $interfaceName
	 */
	public static function setDefaultObject($object, $interfaceName)
	{
		self::addBinding(self::DEFAULT_KEY, $object, $interfaceName);
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
		return self::getObject($caller, self::INTERFACE_ENTITY_MANAGER);
	}

	/**
	 * Assign entity manager instance to caller class
	 *
	 * @param object/string $caller
	 * @param object $object 
	 */
	public static function setEntityManager($caller, $object)
	{
		self::addBinding($caller, $object, self::INTERFACE_ENTITY_MANAGER);
	}

	/**
	 * Set default entity manager
	 *
	 * @param type $object 
	 */
	public static function setDefaultEntityManager($object)
	{
		self::addBinding(self::DEFAULT_KEY, $object, self::INTERFACE_ENTITY_MANAGER);
	}

	/**
	 * Get assigned file storage
	 *
	 * @param string/object $caller
	 * @return object
	 */
	public static function getFileStorage($caller)
	{
		return self::getObject($caller, self::INTERFACE_FILE_STORAGE);
	}

	/**
	 * Assign file storage instance to caller class
	 *
	 * @param string/object $caller
	 * @param object $object 
	 */
	public static function setFileStorage($caller, $object)
	{
		self::addBinding($caller, $object, self::INTERFACE_FILE_STORAGE);
	}

	/**
	 * Set default file storage
	 *
	 * @param object $object 
	 */
	public static function setDefaultFileStorage($object)
	{
		self::addBinding(self::DEFAULT_KEY, $object, self::INTERFACE_FILE_STORAGE);
	}

	/**
	 * Internal relation setter
	 *
	 * @param string $callerClass
	 * @param object $object
	 * @param string $interfaceClass 
	 */
	protected static function addBinding($caller, $object, $interfaceClass)
	{
		if (is_object($caller)) {
			$caller = get_class($caller);
		} else if ( ! is_string($caller)) {
			throw new \RuntimeException('Caller must be class instance or class name');
		}
		if ( ! is_object($object)) {
			throw new \RuntimeException('Object must be an object');
		}
		if ( ! is_a($object, $interfaceClass)) {
			throw new \RuntimeException('Object must be an instance of interface class or must extend it');
		}

		$caller = trim($caller, "\\");
		$interfaceClass = trim($interfaceClass, "\\");

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
		if (empty($objectClass)) {
			return null;
		}

		if (isset(self::$objectBindings[$callerClass], self::$objectBindings[$callerClass][$objectClass])) {
			return self::$objectBindings[$callerClass][$objectClass];
			
		} else if ($callerClass != self::DEFAULT_KEY) {
			$backslashPos = strrpos($callerClass, "\\");
			$seniorClass = self::DEFAULT_KEY;
			if ($backslashPos !== false) {
				$seniorClass = substr($callerClass, 0, $backslashPos);
			}
			return self::findObject($seniorClass, $objectClass);
			
		} else {
			return null;
		}
	}
}
