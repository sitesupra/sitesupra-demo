<?php

namespace Supra\ObjectRepository;

use Doctrine\ORM\EntityManager;
use Supra\FileStorage\FileStorage;
use Supra\User\UserProvider;
use Supra\Log\Writer\WriterAbstraction;
use Supra\Session\SessionNamespace;
use Supra\Session\SessionManager;
use Supra\Log\Log;
use Supra\Locale\LocaleManager;
use Supra\Mailer\Mailer;
use Supra\Authorization\AuthorizationProvider;
use Supra\Cms\ApplicationConfiguration;
use Supra\Search\IndexerQueue;

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
	const INTERFACE_SESSION_NAMESPACE_MANAGER = 'Supra\Session\SessionManager';		
	const INTERFACE_SESSION_NAMESPACE = 'Supra\Session\SessionNamespace';
	const INTERFACE_LOCALE_MANAGER = 'Supra\Locale\LocaleManager';
	const INTERFACE_MAILER = 'Supra\Mailer\Mailer';
	const INTERFACE_AUTHORIZATION_PROVIDER = 'Supra\Authorization\AuthorizationProvider';
	const INTERFACE_APPLICATION_CONFIGURATION = 'Supra\Cms\ApplicationConfiguration';
	const INTERFACE_INDEXER_QUEUE = 'Supra\Search\IndexerQueue';
	
	/**
	 * Object relation storage
	 *
	 * @var array
	 */
	protected static $objectBindings = array(
		self::DEFAULT_KEY => array(),
	);
	
	/**
	 * Called controller stack (id list, last added controller first)
	 * @var array
	 */
	protected static $controllerStack = array();

	/**
	 * Marks beginning of the controller context,
	 * adds the controller ID to the call stack
	 * @param string $controllerId
	 */
	public static function beginControllerContext($controllerId)
	{
		array_unshift(self::$controllerStack, $controllerId);
	}
	
	/**
	 * Marks the end of the controller execution
	 * @param string $expectedControllerId
	 * @throws Exception\LogicException
	 */
	public static function endControllerContext($expectedControllerId)
	{
		$actualControllerId = array_shift(self::$controllerStack);
		
		if ($actualControllerId != $expectedControllerId) {
			
			$expectationString = null;
			
			if (empty($actualControllerId)) {
				$expectationString = "No controller";
			} else {
				$expectationString = "Controller '$actualControllerId'";
			}
			
			throw new Exception\LogicException("$expectationString was expected to be ended, but '$expectedControllerId' was passed");
		}
	}
	
	/**
	 * Shouldn't be called. Used by tests.
	 */
	public function resetControllerContext()
	{
		self::$controllerStack = array();
	}
	
	/**
	 * Normalizes caller, object is converted to the class name string
	 * @param mixed $caller
	 * @return string
	 */
	private static function normalizeCallerArgument($caller)
	{
		if (is_object($caller)) {
			$caller = get_class($caller);
		} elseif ( ! is_string($caller)) {
			throw new Exception\RuntimeException('Caller must be class instance or class name');
		} else {
			$caller = trim($caller, '\\');
		}
		
		return $caller;
	}
	
	/**
	 * Normalizes interface name argument
	 * @param string $interface
	 * @return string
	 */
	private static function normalizeInterfaceArgument($interface)
	{
		if ( ! is_string($interface)) {
			throw new Exception\RuntimeException('Interface argument must be a string');
		}
		
		$interface = trim($interface, '\\');
		
		return $interface;
	}

	/**
	 * Get object of specified interface assigned to caller class
	 *
	 * @param mixed $callerClass
	 * @param string $interface
	 * @return object
	 * @throws Exception\RuntimeException
	 */
	public static function getObject($caller, $interface)
	{
		$interface = self::normalizeInterfaceArgument($interface);
		
		// 1. Try matching any controller from the execution list
		foreach (self::$controllerStack as $controllerId) {
			$object = self::findObject($controllerId, $interface);
			
			if ( ! is_null($object)) {
				return $object;
			}
		}
		
		
		// Experimental: try loading "nearest" objects.
		// Case when object received from repository is requesting other objects.
		// This code will request other objects from the same place the parent object is requested.
//		foreach (self::$objectBindings as $namespace => $objects) {
//			foreach ($objects as $interface => $object) {
//				if ($object === $caller) {
//					$caller = $namespace;
//					break;
//				}
//			}
//		}
		
		// 2. If not found, try matching nearest defined object by caller
		$caller = self::normalizeCallerArgument($caller);
		$object = self::findNearestObject($caller, $interface);

		return $object;
	}

	/**
	 * Assign object of its own class to caller class
	 *
	 * @param mixed $caller
	 * @param object $object 
	 * @param string $interface
	 */
	public static function setObject($caller, $object, $interface)
	{
		self::addBinding($caller, $object, $interface);
	}
	
	/**
	 * Set default assigned object of its class
	 *
	 * @param mixed $object 
	 * @param string $interface
	 */
	public static function setDefaultObject($object, $interface)
	{
		self::addBinding(self::DEFAULT_KEY, $object, $interface);
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
	 * @return SessionManager
	 */
	public static function getSessionManager($caller)
	{
		return self::getObject($caller, self::INTERFACE_SESSION_NAMESPACE_MANAGER);
	}

	/**
	 * Assign session manager to caller class
	 *
	 * @param mixed $caller
	 * @param SessionManager $object 
	 */
	public static function setSessionManager($caller, SessionManager $object)
	{
		self::addBinding($caller, $object, self::INTERFACE_SESSION_NAMESPACE_MANAGER);
	}

	/**
	 * Set default session manager
	 *
	 * @param SessionManager $object 
	 */
	public static function setDefaultSessionManager(SessionManager $object)
	{
		self::addBinding(self::DEFAULT_KEY, $object, self::INTERFACE_SESSION_NAMESPACE_MANAGER);
	}

	/**
	 * Internal relation setter
	 *
	 * @param string $callerClass
	 * @param object $object
	 * @param string $interface 
	 * @throws Exception\RuntimeException
	 */
	protected static function addBinding($caller, $object, $interface)
	{
		$caller = self::normalizeCallerArgument($caller);
		$interface = self::normalizeInterfaceArgument($interface);
		
		if ( ! is_object($object)) {
			throw new Exception\RuntimeException('Object argument must be an object');
		}
		
		if ( ! is_a($object, $interface)) {
			throw new Exception\RuntimeException('Object must be an instance of interface class or must extend it');
		}

		self::$objectBindings[$caller][$interface] = $object;
	}
	
	/**
	 * Find object by exact namespace/classname
	 * @param string $namespace
	 * @param string $objectClass
	 * @return object
	 */
	private static function findObject($namespace, $objectClass)
	{
		if (isset(self::$objectBindings[$namespace][$objectClass])) {
			return self::$objectBindings[$namespace][$objectClass];
		}
	}

	/**
	 * Find object by namespace or it's parent namespaces
	 * @param string $namespace
	 * @param string $objectClass 
	 */
	private static function findNearestObject($namespace, $objectClass)
	{
		$object = null;
		
		do {
			$object = self::findObject($namespace, $objectClass);
			$namespace = self::getParentNamespace($namespace);
		} while (is_null($object) && ! is_null($namespace));
		
		return $object;
	}
	
	private static function getParentNamespace($namespace)
	{
		if ($namespace === self::DEFAULT_KEY) {
			return null;
		}
		
		// Try parent namespace
		$backslashPos = strrpos($namespace, "\\");
		$seniorClass = null;

		if ($backslashPos !== false) {
			$seniorClass = substr($namespace, 0, $backslashPos);
		} else {
			$seniorClass = self::DEFAULT_KEY;
		}

		return $seniorClass;
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
	 * Assign mailer instance to caller class
	 *
	 * @param mixed $caller
	 * @param Mailer $object
	 */
	public static function setMailer($caller, Mailer $object)
	{
		self::addBinding($caller, $object, self::INTERFACE_MAILER);
	}

	/**
	 * Set default mailer
	 *
	 * @param Mailer $object 
	 */
	public static function setDefaultMailer(Mailer $object)
	{
		self::addBinding(self::DEFAULT_KEY, $object, self::INTERFACE_MAILER);
	}

	/**
	 * Get assigned mailer
	 *
	 * @param mixed $caller
	 * @return Mailer
	 */
	public static function getMailer($caller)
	{
		return self::getObject($caller, self::INTERFACE_MAILER);
	}
	
	/**
	 * Get assigned authorization provider.
	 *
	 * @param mixed $caller
	 * @return AuthorizationProvider
	 */
	public static function getAuthorizationProvider($caller)
	{
		return self::getObject($caller, self::INTERFACE_AUTHORIZATION_PROVIDER);
	}

	/**
	 * Assign autorization provider to class.
	 *
	 * @param mixed $caller
	 * @param AuthorizationProvider $object 
	 */
	public static function setAuthorizationProvider($caller, AuthorizationProvider $object)
	{
		self::addBinding($caller, $object, self::INTERFACE_AUTHORIZATION_PROVIDER);
	}
	
	/**
	 * Set default authorization provider.
	 *
	 * @param AuthorizationProvider $object 
	 */
	public static function setDefaultAuthorizationProvider(AuthorizationProvider $object)
	{
		self::addBinding(self::DEFAULT_KEY, $object, self::INTERFACE_AUTHORIZATION_PROVIDER);
	}	
	
 /**
	 * Get assigned application configuration.
	 *
	 * @param mixed $caller
	 * @return ApplicationConfiguration
	 */
	public static function getApplicationConfiguration($caller)
	{
		return self::getObject($caller, self::INTERFACE_APPLICATION_CONFIGURATION);
	}

	/**
	 * Assign application configuration to namespace
	 *
	 * @param mixed $caller
	 * @param AuthorizationProvider $object 
	 */
	public static function setApplicationConfiguration($caller, ApplicationConfiguration $object)
	{
		self::addBinding($caller, $object, self::INTERFACE_APPLICATION_CONFIGURATION);
	}
	
	/**
	 * Set application configuration.
	 *
	 * @param AuthorizationProvider $object 
	 */
	public static function setDefaultApplicationConfiguration(ApplicationConfiguration $object)
	{
		self::addBinding(self::DEFAULT_KEY, $object, self::INTERFACE_APPLICATION_CONFIGURATION);
	}	

	/**
	 * Get assigned indexer queue.
	 *
	 * @param mixed $caller
	 * @return IndexerQueue
	 */
	public static function getIndexerQueue($caller)
	{
		return self::getObject($caller, self::INTERFACE_INDEXER_QUEUE);
	}

	/**
	 * Assign indexer queue to namespace.
	 *
	 * @param mixed $caller
	 * @param IndexerQueue $object 
	 */
	public static function setIndexerQueue($caller, IndexerQueue $object)
	{
		self::addBinding($caller, $object, self::INTERFACE_INDEXER_QUEUE);
	}
	
	/**
	 * Set default indexer queue.
	 *
	 * @param IndexerQueue $object 
	 */
	public static function setDefaultIndexerQueue(IndexerQueue $object)
	{
		self::addBinding(self::DEFAULT_KEY, $object, self::INTERFACE_INDEXER_QUEUE);
	}	

}
