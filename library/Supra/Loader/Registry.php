<?php

namespace Supra\Loader;

/**
 * Loader registry class
 */
class Registry
{
	/**
	 * The singleton instance
	 * @var \Supra\Loader\Registry
	 */
	protected static $instance;

	/**
	 * List of registered namespace paths
	 * @var \Supra\Loader\NamespaceRecord
	 */
	protected $registry = array();

	/**
	 * Whether the registry array is sorted by depth descending
	 * @var boolean
	 */
	protected $registryOrdered = true;

	/**
	 * Generate instance of the loader
	 * @return \Supra\Loader\Registry
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
	 * @param string $namespace
	 * @param string $path
	 */
	public function registerNamespace(NamespaceRecord $namespaceRecord)
	{
		$this->registry[] = $namespaceRecord;
		$this->registryOrdered = false;
	}

	public function registerRootNamespace($path)
	{
		$namespaceRecord = new NamespaceRecord('', $path);
		$this->registerNamespace($namespaceRecord);
	}

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

	public static function normalizeNamespaceName($namespace)
	{
		return '\\' . ltrim(rtrim($namespace, '\\') . '\\', '\\');
	}

	public static function normalizeClassName($class)
	{
		return '\\' . ltrim($class, '\\');
	}

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

	public function autoload($className)
	{
		return $this->load($className);
	}
}