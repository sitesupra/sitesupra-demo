<?php

namespace Supra\Loader\Strategy;

/**
 * Loader strategy by PEAR standards, replacing hieararchy with underscores
 */
class PearLoaderStrategy implements LoaderStrategyInterface
{
	/**
	 * @var string
	 */
	private $prefix;
	
	/**
	 * @var string
	 */
	private $path;
	
	/**
	 * @var string
	 */
	private $depth;
	
	/**
	 * @var string
	 */
	private $length;
	
	/**
	 * Whether to include the prefix in building the file path
	 * @var type 
	 */
	private $includePrefix = false;
	
	public function __construct($prefix, $path, $includePrefix = false)
	{
		$this->prefix = $prefix;
		$this->path = $path;
		$this->depth = substr_count($prefix, '_');
		$this->length = strlen($prefix);
		$this->includePrefix = $includePrefix;
	}
	
	/**
	 * {@inheritdoc}
	 * @return int
	 */
	public function getDepth()
	{
		return $this->depth;
	}
	
	/**
	 * {@inheritdoc}
	 * @param string $className
	 * @return string
	 */
	public function findClass($className)
	{
		if ($className != $this->prefix && strpos($className, $this->prefix . '_') !== 0) {
			return null;
		}
		
		if ( ! $this->includePrefix) {
			$className = substr($className, $this->length);
		}
		
		$fileName = str_replace('_', DIRECTORY_SEPARATOR, $className);
		$fileName = $this->path . $fileName . '.php';
		
		return $fileName;
	}
}
