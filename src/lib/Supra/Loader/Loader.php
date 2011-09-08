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

			$orderFunction = function(LoaderStrategyInterface $a, LoaderStrategyInterface $b)
			{
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
	 * Try loading class by it's name
	 * @param string $className
	 * @return boolean
	 */
	public function load($className)
	{
		$this->orderStrategies();

		$className = static::normalizeClassName($className);

		foreach ($this->strategies as $strategy) {
			$classPath = $strategy->findClass($className);
			if ( ! is_null($classPath)) {
				require_once $classPath;
				
				return true;
			}
		}

		return false;
	}

	/**
	 * Autoload method
	 * @param string $className
	 * @return boolean
	 */
	public function autoload($className)
	{
		return $this->load($className);
	}
	
	/**
	 * Registers the autoloader
	 */
	public function registerSystemAutoload()
	{
		spl_autoload_register(array($this, 'autoload'));
	}
}