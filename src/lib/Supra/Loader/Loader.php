<?php

namespace Supra\Loader;

use Supra\Loader\Strategy\LoaderStrategyInterface;

/**
 * Loader registry class
 */
class Loader
{

	/**
	 *
	 * @var array
	 */
	static $cachedClasses = array();
	static $hit = 0;
	static $miss = 0;
	static $cacheEnabled = false;
	static $cacheFilenameSuffix = 'default';

	/**
	 * The singleton instance
	 * @var Loader
	 */
	protected static $instance;

	/**
	 * List of registered namespace paths
	 * @var Strategy\NamespaceLoaderStrategy
	 */
	protected $strategies = array();

	/**
	 * Whether the registry array is sorted by depth descending
	 * @var boolean
	 */
	protected $strategiesOrdered = true;

	/**
	 * Generate instance of the loader
	 * @return Loader
	 */
	public static function getInstance()
	{
		if (is_null(self::$instance)) {

			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * 
	 */
	static function enableCache($cacheFilenameSuffix)
	{
		self::$cacheEnabled = true;
		self::$cacheFilenameSuffix = $cacheFilenameSuffix;

		$cacheFilename = self::getLoadedClassesCacheFiename();

		if (file_exists($cacheFilename)) {

			$loadedClasses = array();
			include_once($cacheFilename);
			self::$cachedClasses = $loadedClasses;
		}

		register_shutdown_function(array(__CLASS__, 'shutdown'));
	}

	/**
	 * @return string
	 */
	static function getLoadedClassesCacheFiename()
	{
		return sys_get_temp_dir() . '/autoload-cache-' . self::$cacheFilenameSuffix . '.php';
	}

	/**
	 * Binds the namespace classses to be searched in the path specified
	 * @param LoaderStrategyInterface $strategy
	 */
	public function registerNamespace(LoaderStrategyInterface $strategy)
	{
		$this->strategies[] = $strategy;
		$this->strategiesOrdered = false;
	}

	/**
	 * Register root "\" namespace
	 * @param string $path
	 */
	public function registerRootNamespace($path)
	{
		$strategy = new LoaderStrategyInterface('', $path);
		$this->registerNamespace($strategy);
	}

	/**
	 * Order registred namespaces by depth
	 */
	protected function orderStrategies()
	{
		$orderFunction = function(LoaderStrategyInterface $a, LoaderStrategyInterface $b) {
					$aDepth = $a->getDepth();
					$bDepth = $b->getDepth();

					if ($aDepth == $bDepth) {
						return 0;
					}

					return $aDepth < $bDepth ? 1 : -1;
				};

		usort($this->strategies, $orderFunction);

		$this->strategiesOrdered = true;
	}

	/**
	 * Normalize namespace name by removing \ in the front and adding in the end
	 * @param string $namespace
	 * @return string
	 */
	public static function normalizeNamespaceName($namespace)
	{
		return ltrim(rtrim($namespace, '\\') . '\\', '\\');
	}

	/**
	 * Normalize class name by trimming the separator
	 * @param string $class
	 * @return string
	 */
	public static function normalizeClassName($class)
	{
		return ltrim($class, '\\');
	}

	/**
	 * Find class path by its name
	 * @param string $className
	 * @return string
	 */
	public function findClassPath($className)
	{
		if ( ! $this->strategiesOrdered) {
			$this->orderStrategies();
		}

		$className = static::normalizeClassName($className);

		foreach ($this->strategies as $strategy) {
			$classPath = $strategy->findClass($className);

			if ( ! is_null($classPath)) {
				return $classPath;
			}
		}

		return null;
	}

	/**
	 * 
	 * @param string $className
	 * @return boolean
	 */
	protected function doAutoload($className)
	{
		if ( ! $this->strategiesOrdered) {
			$this->orderStrategies();
		}

		$classPath = $this->findClassPath($className);

		if ( ! is_null($classPath)) {

			$included = include_once $classPath;

			return (bool) $included;
		}

		return false;
	}

	/**
	 * Autoload method, try loading class by it's name
	 * @param string $className
	 * @return boolean
	 */
	public function autoload($className)
	{
		if (self::$cacheEnabled) {

			if (isset(self::$cachedClasses[$className])) {

				self::$hit ++;
				$classPath = self::$cachedClasses[$className];
			} else {

				self::$miss ++;
				$classPath = null;
			}

			if (empty($classPath)) {

				$classPath = $this->findClassPath($className);

				if ( ! empty($classPath)) {
					$loaded = include_once $classPath;
				} else {
					$loaded = false;
				}
			} else {

				$loaded = include_once $classPath;
			}

			//if ($loaded) {
			self::$cachedClasses[$className] = $classPath;
			//}
		} else {

			$classPath = $this->findClassPath($className);

			if ( ! empty($classPath)) {
				$loaded = include_once $classPath;
			} else {
				$loaded = false;
			}
		}

		return $loaded;
	}

	/**
	 * Autoload method, try loading class by it's name
	 * Checks for file existence, does not load class and does not provide warning messages if it does not exist  
	 * 
	 * @param string $className
	 * @return boolean
	 */
	public function silentAutoload($className)
	{
		if ( ! $this->strategiesOrdered) {
			$this->orderStrategies();
		}

		$classPath = $this->findClassPath($className);

		if ( ! is_null($classPath)) {

			$included = false;
			if (file_exists($classPath)) {
				$included = include_once $classPath;
			}

			return (bool) $included;
		}

		return false;
	}

	/**
	 * Registers the autoloader
	 */
	public function registerSystemAutoload()
	{
		spl_autoload_register(array($this, 'autoload'));
	}

	/**
	 * Get instance of $className that extends or implements $interface.
	 * NB! Will raise warnings if such class/interface file does not exist.
	 *
	 * @param string $className
	 * @param string $interface 
	 * @return object
	 * @throws Supra\Loader\Exception\ClassMismatch
	 * @throws Supra\Loader\Exception\InterfaceNotFound
	 * @throws Supra\Loader\Exception\ClassNotFound
	 */
	public static function getClassInstance($className, $interface = null)
	{
		if ( ! class_exists($className)) {
			throw new Exception\ClassNotFound($className);
		}

		$object = new $className();
		if ( ! is_null($interface) && is_string($interface)) {
			if ( ! class_exists($interface) && ! interface_exists($interface)) {
				throw new Exception\InterfaceNotFound($className);
			}
			if ( ! $object instanceof $interface) {
				throw new Exception\ClassMismatch($className, $interface);
			}
		}

		return $object;
	}

	/**
	 * Search for the class without any messages about "include_once" failures
	 * which will appear using the class_exists() function.
	 * @param string $className
	 * @return boolean
	 */
	public static function classExists($className)
	{
		// Already loaded
		if (class_exists($className, false)) {
			return true;
		}

		$classPath = self::getInstance()
				->findClassPath($className);

		// This is the case when include_once will warn you
		if ( ! is_null($classPath) && ! file_exists($classPath)) {
			return false;
		}

		// Use standard loader otherwise
		return class_exists($className, true);
	}

	/**
	 * Search for the interface without any messages about "include_once" failures
	 * which will appear using the interface_exists() function.
	 * @param string $className
	 * @return boolean
	 */
	public static function interfaceExists($className)
	{
		// Already loaded
		if (interface_exists($className, false)) {
			return true;
		}

		$classPath = self::getInstance()
				->findClassPath($className);

		// This is the case when include_once will warn you
		if ( ! is_null($classPath) && ! file_exists($classPath)) {
			return false;
		}

		// Use standard loader otherwise
		return interface_exists($className, true);
	}

	/**
	 * 
	 */
	static function shutdown()
	{
		//\Log::error('HIT: ', self::$hit);
		//\Log::error('MISS: ', self::$miss);
		//\Log::error('STORING CACHE: ', count(self::$cachedClasses));

		file_put_contents(self::getLoadedClassesCacheFiename(), '<?php $loadedClasses = ' . var_export(self::$cachedClasses, true) . ';');
	}

}