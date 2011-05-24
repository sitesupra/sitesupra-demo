<?php

namespace Supra\Loader;

/**
 * Loader registry class
 */
class Registry
{
	/**
	 * The singleton instance
	 * @var Registry
	 */
	protected static $instance;

	/**
	 * List of registered namespace paths
	 * @var NamespaceRecord
	 */
	protected $registry = array();

	/**
	 * Whether the registry array is sorted by depth descending
	 * @var boolean
	 */
	protected $registryOrdered = true;

	/**
	 * Generate instance of the loader
	 * @return Registry
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
	 * @param NamespaceRecord $namespaceRecord
	 */
	public function registerNamespace(NamespaceRecord $namespaceRecord)
	{
		$this->registry[] = $namespaceRecord;
		$this->registryOrdered = false;
	}

	/**
	 * Register root "\" namespace
	 * @param string $path
	 */
	public function registerRootNamespace($path)
	{
		$namespaceRecord = new NamespaceRecord('', $path);
		$this->registerNamespace($namespaceRecord);
	}

	/**
	 * Order registred namespaces by depth
	 */
	protected function orderRegistry()
	{
		if ( ! $this->registryOrdered) {

			$orderFunction = function(NamespaceRecord $a, NamespaceRecord $b)
			{
				$aDepth = $a->getDepth();
				$bDepth = $b->getDepth();
				
				if ($aDepth == $bDepth) {
					return 0;
				}
				
				return $aDepth < $bDepth ? 1 : -1;
			};

			usort($this->registry, $orderFunction);

			$this->registryOrdered = true;
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
		$this->orderRegistry();

		$className = static::normalizeClassName($className);

		foreach ($this->registry as $namespaceRecord) {
			$classPath = $namespaceRecord->findClass($className);
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