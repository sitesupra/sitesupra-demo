<?php

namespace Supra\Loader;

/**
 * Namespace record in the loader registry
 */
class NamespaceRecord
{

	protected $namespace;

	protected $path;

	protected $length;

	protected $depth;

	public function __construct($namespace, $path)
	{
		$this->setNamespace($namespace);
		$this->setPath($path);

		$this->length = strlen($this->getNamespace());
		$this->depth = substr_count($this->getNamespace(), '\\');
		
	}

	public function setNamespace($namespace)
	{
		$namespace = Registry::normalizeNamespaceName($namespace);
		$this->namespace = $namespace;
	}

	public function getNamespace()
	{
		return $this->namespace;
	}

	public function setPath($path)
	{
		$this->path = rtrim($path, '/\\') . \DIRECTORY_SEPARATOR;
	}

	public function getPath()
	{
		return $this->path;
	}

	public function getLength()
	{
		return $this->length;
	}

	public function getDepth()
	{
		return $this->depth;
	}

	public function contains($className)
	{
		$classNamespace = substr($className, 0, $this->getLength());
		return $classNamespace == $this->getNamespace();
	}

	public function findClass($className)
	{
		if ( ! $this->contains($className)) {
			return null;
		}
		$namespacePath = $this->getPath();
		$classPath = substr($className, $this->getLength());
		$classPath = str_replace('\\', \DIRECTORY_SEPARATOR, $classPath);

		$classPath = $namespacePath . $classPath . '.php';
		if ( ! file_exists($classPath)) {
			return null;
			/*throw new Exception("Class ${className} should be contained inside"
					. " the ${namespacePath} namespace but could not be found"
					. " by path ${classPath}");*/
		}

		return $classPath;
	}
}