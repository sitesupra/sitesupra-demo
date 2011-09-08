<?php

namespace Supra\Loader\Strategy;

/**
 * Interface for autoloader strategy class
 */
interface LoaderStrategyInterface
{
	/**
	 * Get priority of the loader
	 * @return int
	 */
	public function getDepth();
	
	/**
	 * Search for class and return it's path if succeeds
	 * @param string $className
	 * @return string
	 */
	public function findClass($className);
}
