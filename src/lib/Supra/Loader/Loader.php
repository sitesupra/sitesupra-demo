<?php

namespace Supra\Loader;

use Supra\Loader\Strategy\LoaderStrategyInterface;

/**
 * Loader registry class
 */
class Loader
{
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
		if ( ! $this->strategiesOrdered) {

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
	}

	/**
	 * Normalize namespace name by appending \ in the front and end
	 * @param string $namespace
	 * @return string
	 */
	public static function normalizeNamespaceName($namespace)
	{
		return '\\' . ltrim(rtrim($namespace, '\\') . '\\', '\\');
	}

	/**
	 * Normalize class name by appending \ in the front
	 * @param string $class
	 * @return string
	 */
	public static function normalizeClassName($class)
	{
		return '\\' . ltrim($class, '\\');
	}

	/**
	 * Find class path by its name
	 * @param string $className
	 * @return string
	 */
	public function findClassPath($className)
	{
		$this->orderStrategies();

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
	 * Autoload method, try loading class by it's name
	 * @param string $className
	 * @return boolean
	 */
	public function autoload($className)
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
	 * Registers the autoloader
	 */
	public function registerSystemAutoload()
	{
		spl_autoload_register(array($this, 'autoload'));
	}

	/**
	 * Get instance of $className that extends or implements $interface
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
		if ( ! self::classExists($className)) {
			throw new Exception\ClassNotFound($className);
		}

		$object = new $className();
		if ( ! is_null($interface) && is_string($interface)) {
			if ( ! self::classExists($interface) && ! self::interfaceExists($interface)) {
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
}