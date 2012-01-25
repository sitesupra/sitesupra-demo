<?php

namespace Supra\Loader\Strategy;

use Supra\Loader\Loader;
use Supra\Loader\Exception;

/**
 * Strategy to find class according to the namespace
 */
class NamespaceLoaderStrategy implements LoaderStrategyInterface
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
		$namespace = Loader::normalizeNamespaceName($namespace);
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
		$this->path = rtrim($path, '/\\') . DIRECTORY_SEPARATOR;
		if ( ! is_dir($this->path)) {
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
		return (strpos($className, $this->namespace) === 0);
	}
	
	/**
	 * Standard class-path mapping function
	 * @param string $classPath
	 * @return string
	 */
	public function convertToFilePath($classPath)
	{
		$filePath = str_replace('\\', DIRECTORY_SEPARATOR, $classPath);
		$filePath = $filePath . '.php';
		
		return $filePath;
	}

	/**
	 * {@inheritdoc}
	 * @param string $className
	 * @return string
	 */
	public function findClass($className)
	{
		if ( ! $this->contains($className)) {
			return null;
		}
		$namespacePath = $this->path;
		$classPath = substr($className, $this->length);

		$filePath = $this->convertToFilePath($classPath);
		
		$filePath = $namespacePath . $filePath;
		
		// Disabled for performance
//		if ( ! file_exists($filePath)) {
//			return null;
//			/*throw new Exception\ClassNotFound("Class ${className} should be contained inside"
//					. " the ${namespacePath} namespace but could not be found"
//					. " by path ${classPath}");*/
//		}

		return $filePath;
	}
}
