<?php

namespace Supra\ObjectRepository;

use Doctrine\ORM\EntityManager;
use Supra\FileStorage\FileStorage;
use Supra\User\UserProvider;
use Supra\Log\Writer\WriterAbstraction;
use Supra\Session\SessionNamespace;
use Supra\Session\SessionNamespaceManager;
use Supra\Log\Log;
use Supra\Locale\LocaleManager;
use Supra\Authorization\AuthorizationProvider;
/**
 * Object repository
 */
class ObjectRepository
{
	const DEFAULT_KEY = '';
	
	const INTERFACE_LOGGER = 'Supra\Log\Writer\WriterAbstraction';
	const INTERFACE_FILE_STORAGE = 'Supra\FileStorage\FileStorage';
	const INTERFACE_USER_PROVIDER = 'Supra\User\UserProvider';
	const INTERFACE_ENTITY_MANAGER = 'Doctrine\ORM\EntityManager';
	const INTERFACE_SESSION_NAMESPACE_MANAGER = 'Supra\Session\SessionNamespaceManager';		
	const INTERFACE_SESSION_NAMESPACE = 'Supra\Session\SessionNamespace';
	const INTERFACE_LOCALE_MANAGER = 'Supra\Locale\LocaleManager';
	const INTERFACE_AUTHORIZATION_PROVIDER = 'Supra\Authorization\AuthorizationProvider';

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
	 * @param mixed $callerClass
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
		$interfaceName = trim($interfaceName, "\\");
		
		$object = self::findObject($caller, $interfaceName);

		return $object;
	}

	/**
	 * Assign object of its own class to caller class
	 *
	 * @param mixed $caller
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
	 * @param mixed $object 
	 * @param string $interfaceName
	 */
	public static function setDefaultObject($object, $interfaceName)
	{
		self::addBinding(self::DEFAULT_KEY, $object, $interfaceName);
	}

	/**
	 * Get assigned logger
	 *
	 * @param mixed $caller
	 * @return WriterAbstraction
	 */
	public static function getLogger($caller)
	{
		$logger = self::getObject($caller, self::INTERFACE_LOGGER);

		// Create bootstrap logger in case of missing logger
		if (empty($logger)) {
			$logger = Log::getBootstrapLogger();
			self::setDefaultLogger($logger);
		}

		return $logger;
	}

	/**
	 * Assign logger instance to caller class
	 *
	 * @param mixed $caller
	 * @param WriterAbstraction $object 
	 */
	public static function setLogger($caller, WriterAbstraction $object)
	{
		self::addBinding($caller, $object, self::INTERFACE_LOGGER);
	}

	/**
	 * Set default logger
	 *
	 * @param WriterAbstraction $object 
	 */
	public static function setDefaultLogger(WriterAbstraction $object)
	{
		self::addBinding(self::DEFAULT_KEY, $object, self::INTERFACE_LOGGER);
	}

	/**
	 * Get entity manager assigned to caller class
	 *
	 * @param mixed $caller
	 * @return EntityManager
	 */
	public static function getEntityManager($caller)
	{
		return self::getObject($caller, self::INTERFACE_ENTITY_MANAGER);
	}

	/**
	 * Assign entity manager instance to caller class
	 *
	 * @param mixed $caller
	 * @param EntityManager $object 
	 */
	public static function setEntityManager($caller, EntityManager $object)
	{
		self::addBinding($caller, $object, self::INTERFACE_ENTITY_MANAGER);
	}

	/**
	 * Set default entity manager
	 *
	 * @param EntityManager $object 
	 */
	public static function setDefaultEntityManager(EntityManager $object)
	{
		self::addBinding(self::DEFAULT_KEY, $object, self::INTERFACE_ENTITY_MANAGER);
	}

	/**
	 * Get assigned file storage
	 *
	 * @param mixed $caller
	 * @return FileStorage
	 */
	public static function getFileStorage($caller)
	{
		return self::getObject($caller, self::INTERFACE_FILE_STORAGE);
	}

	/**
	 * Assign file storage instance to caller class
	 *
	 * @param mixed $caller
	 * @param FileStorage $object 
	 */
	public static function setFileStorage($caller, FileStorage $object)
	{
		self::addBinding($caller, $object, self::INTERFACE_FILE_STORAGE);
	}

	/**
	 * Set default file storage
	 *
	 * @param FileStorage $object 
	 */
	public static function setDefaultFileStorage(FileStorage $object)
	{
		self::addBinding(self::DEFAULT_KEY, $object, self::INTERFACE_FILE_STORAGE);
	}

	/**
	 * Get assigned session namespace
	 *
	 * @param mixed $caller
	 * @return SessionNamespace
	 */
	public static function getSessionNamespace($caller)
	{
		return self::getObject($caller, self::INTERFACE_SESSION_NAMESPACE);
	}

	/**
	 * Assign session manager to caller class
	 *
	 * @param mixed $caller
	 * @param SessionNamespace $object 
	 */
	public static function setSessionNamespace($caller, SessionNamespace $object)
	{
		self::addBinding($caller, $object, self::INTERFACE_SESSION_NAMESPACE);
	}

	/**
	 * Set default session manager
	 *
	 * @param SessionNamespace $object 
	 */
	public static function setDefaultSessionNamespace(SessionNamespace $object)
	{
		self::addBinding(self::DEFAULT_KEY, $object, self::INTERFACE_SESSION_NAMESPACE);
	}

	/**
	 * Get assigned session namespace
	 *
	 * @param mixed $caller
	 * @return SessionNamespaceManager
	 */
	public static function getSessionNamespaceManager($caller)
	{
		return self::getObject($caller, self::INTERFACE_SESSION_NAMESPACE_MANAGER);
	}

	/**
	 * Assign session manager to caller class
	 *
	 * @param mixed $caller
	 * @param SessionNamespaceManager $object 
	 */
	public static function setSessionNamespaceManager($caller, SessionNamespaceManager $object)
	{
		self::addBinding($caller, $object, self::INTERFACE_SESSION_NAMESPACE_MANAGER);
	}

	/**
	 * Set default session manager
	 *
	 * @param SessionNamespaceManager $object 
	 */
	public static function setDefaultSessionNamespaceManager(SessionNamespaceManager $object)
	{
		self::addBinding(self::DEFAULT_KEY, $object, self::INTERFACE_SESSION_NAMESPACE_MANAGER);
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
		while ( ! isset(self::$objectBindings[$callerClass][$objectClass])) {
			
			// Event the default instance does not exist
			if ($callerClass == self::DEFAULT_KEY) {
				return null;
			}
			
			// Try parent namespace
			$backslashPos = strrpos($callerClass, "\\");
			$seniorClass = self::DEFAULT_KEY;
			
			if ($backslashPos !== false) {
				$seniorClass = substr($callerClass, 0, $backslashPos);
			}
			
			$callerClass = $seniorClass;
		}
		
		return self::$objectBindings[$callerClass][$objectClass];
	}
	
	/**
	 * Get assigned user provider
	 *
	 * @param mixed $caller
	 * @return UserProvider
	 */
	public static function getUserProvider($caller)
	{
		return self::getObject($caller, self::INTERFACE_USER_PROVIDER);
	}

	/**
	 * Assign user provider instance to caller class
	 *
	 * @param mixed $caller
	 * @param UserProvider $object 
	 */
	public static function setUserProvider($caller, UserProvider $object)
	{
		self::addBinding($caller, $object, self::INTERFACE_USER_PROVIDER);
	}
	
	/**
	 * Set default user provider
	 *
	 * @param UserProvider $object 
	 */
	public static function setDefaultUserProvider(UserProvider $object)
	{
		self::addBinding(self::DEFAULT_KEY, $object, self::INTERFACE_USER_PROVIDER);
	}
	
	/**
	 * Get assigned locale manager
	 *
	 * @param mixed $caller
	 * @return LocaleManager
	 */
	public static function getLocaleManager($caller)
	{
		return self::getObject($caller, self::INTERFACE_LOCALE_MANAGER);
	}

	/**
	 * Assign locale manager instance to caller class
	 *
	 * @param mixed $caller
	 * @param LocaleManager $object 
	 */
	public static function setLocaleManager($caller, LocaleManager $object)
	{
		self::addBinding($caller, $object, self::INTERFACE_LOCALE_MANAGER);
	}
	
	/**
	 * Set default locale manager
	 *
	 * @param LocaleManager $object 
	 */
	public static function setDefaultLocaleManager(LocaleManager $object)
	{
		self::addBinding(self::DEFAULT_KEY, $object, self::INTERFACE_LOCALE_MANAGER);
	}
	
	
	/**
	 * Get assigned locale manager
	 *
	 * @param mixed $caller
	 * @return AuthorizationProvider
	 */
	public static function getAuthorizationProvider($caller)
	{
		return self::getObject($caller, self::INTERFACE_AUTHORIZATION_PROVIDER);
	}

	/**
	 * Assign locale manager instance to caller class
	 *
	 * @param mixed $caller
	 * @param AuthorizationProvider $object 
	 */
	public static function setAuthorizationProvider($caller, AuthorizationProvider $object)
	{
		self::addBinding($caller, $object, self::INTERFACE_AUTHORIZATION_PROVIDER);
	}
	
	/**
	 * Set default locale manager
	 *
	 * @param AuthorizationProvider $object 
	 */
	public static function setDefaultAuthorizationProvider(AuthorizationProvider $object)
	{
		self::addBinding(self::DEFAULT_KEY, $object, self::INTERFACE_AUTHORIZATION_PROVIDER);
	}	
}
