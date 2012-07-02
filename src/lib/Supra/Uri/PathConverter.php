<?php

namespace Supra\Uri;

use Supra\Loader\Loader;

class PathConverter
{

	/**
	 * Returns concatenated object path and provided $path if it is accessable from web 
	 * 
	 * @param object|string $context object or classname
	 * @param string $path
	 * @return string 
	 */
	public static function getWebPath($context, $path)
	{
		// getting classPath from object, classname
		$classPath = static::getClassPath($context);

		// expand all symbolic links and resolve references
		$webroot = realpath(SUPRA_WEBROOT_PATH);
		$webrootCharactersCount = strlen($webroot);
		
		// checking for webroot 
		if (strpos($classPath, $webroot) !== 0) {
			throw new Exception\RuntimeException('File is not located in web path');
		}

		if ( ! is_dir($classPath)) {
			$classPath = dirname($classPath);
		}
		
		$classPath = substr($classPath, $webrootCharactersCount);
		$classPath = str_replace('\\', '/', $classPath);

		$path = str_replace('\\', '/', $path);
		$pathParts = explode('/', $path);
		$path = null;

		foreach ($pathParts as $pathPart) {
			$path .= rawurlencode($pathPart) . '/';
		}

		$path = trim($path, '\\/');

		return "{$classPath}/{$path}";
	}
	
	

	/**
	 * Returns object's system path
	 * 
	 * @param mixed $className
	 * @return string
	 */
	protected static function getClassPath($className)
	{
		if (is_object($className)) {
			$className = get_class($className);
		}

		$loader = Loader::getInstance();

		$classPath = $loader->findClassPath($className);

		if (empty($classPath)) {
			throw new Exception\RuntimeException('Could not find system path for class ' . $className);
		}

		return $classPath;
	}

}
