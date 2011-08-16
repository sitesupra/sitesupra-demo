<?php

namespace Supra\Loader;

use Supra\Loader\Exception;

/**
 * Namespace record in the loader registry
 */
class NamespaceRecord
{
	/**
	 * @var string
	 */
	protected $namespace;

	/**
	 * @var string
	 */
	protected $path;

	/**
	 * @var int
	 */
	protected $length;

	/**
	 * @var int
	 */
	protected $depth;

	/**
	 * @param int $namespace
	 * @param int $path
	 */
	public function __construct($namespace, $path)
	{
		$this->setNamespace($namespace);
		$this->setPath($path);

		$this->length = strlen($this->getNamespace());
		$this->depth = substr_count($this->getNamespace(), '\\');
	}

	/**
	 * @param string $namespace
	 */
	public function setNamespace($namespace)
	{
		$namespace = Registry::normalizeNamespaceName($namespace);
		$this->namespace = $namespace;
	}

	/**
	 * @return string
	 */
	public function getNamespace()
	{
		return $this->namespace;
	}

	/**
	 * @param string $path
	 */
	public function setPath($path)
	{
		$this->path = rtrim($path, '/\\') . \DIRECTORY_SEPARATOR;
		if ( ! \is_dir($this->path)) {
			throw new Exception\InvalidPath($path);
		}
	}

	/**
	 * @return string
	 */
	public function getPath()
	{
		return $this->path;
	}

	/**
	 * @return int
	 */
	public function getLength()
	{
		return $this->length;
	}

	/**
	 * @return int
	 */
	public function getDepth()
	{
		return $this->depth;
	}

	/**
	 * Whether the class is inside the given namespace
	 * @param string $className
	 * @return bool
	 */
	public function contains($className)
	{
		$classNamespace = substr($className, 0, $this->getLength());
		return $classNamespace == $this->getNamespace();
	}
	
	/**
	 * Standard class-path mapping function
	 * @param string $classPath
	 * @return string
	 */
	public function convertToFilePath($classPath)
	{
		$filePath = str_replace('\\', \DIRECTORY_SEPARATOR, $classPath);
		$filePath = $filePath . '.php';
		
		return $filePath;
	}

	/**
	 * Search for class and return it's path if succeeds
	 * @param string $className
	 * @return string
	 */
	public function findClass($className)
	{
		if ( ! $this->contains($className)) {
			return null;
		}
		$namespacePath = $this->getPath();
		$classPath = substr($className, $this->getLength());

		$filePath = $this->convertToFilePath($classPath);
		
		$filePath = $namespacePath . $filePath;
		if ( ! file_exists($filePath)) {
			return null;
			/*throw new Exception\ClassNotFound("Class ${className} should be contained inside"
					. " the ${namespacePath} namespace but could not be found"
					. " by path ${classPath}");*/
		}

		return $filePath;
	}
}