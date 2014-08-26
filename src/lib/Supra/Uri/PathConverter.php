<?php

namespace Supra\Uri;

use Supra\Loader\Loader;

class PathConverter
{

	/**
	 * Returns concatenated object path and provided $path if it is accessable from web 
	 * 
	 * @param string $path
	 * @param object|string $context object or classname
	 * @return string 
	 */
	public static function getWebPath($path, $context = null)
	{
		$classPath = '';
		
		if ( ! is_null($context)) {
			// getting classPath from object, classname
			$classPath = static::getClassPath($context);
			if ( ! is_dir($classPath)) {
				$classPath = dirname($classPath);
			}
			
		}

		$webroot = SUPRA_WEBROOT_PATH;
		$webrootCharactersCount = strlen($webroot);
		
		$path = trim($path, '/' . DIRECTORY_SEPARATOR);
		$path = $classPath . DIRECTORY_SEPARATOR . $path;
	
                $path = substr($path, strlen(realpath(SUPRA_COMPONENT_PATH)));
                $path = $webroot . $path;
                
		// checking for webroot 
		if (strpos($path, $webroot) !== 0) {
			throw new Exception\RuntimeException("File '$path' doesn't seem to be located in web path");
		}
		
		$path = substr($path, $webrootCharactersCount);
		$path = str_replace(DIRECTORY_SEPARATOR, '/', $path);
		$path = trim($path, '/');

		$pathParts = explode('/', $path);
		$path = '';

		foreach ($pathParts as &$pathPart) {
			$path .= '/' . rawurlencode($pathPart);
		}

		return $path;
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

                $composerLoader = \ComposerAutoloaderInitSupra::getLoader();
                
                $classPath = $composerLoader->findFile($className);
                
		//$loader = Loader::getInstance();
		//$classPath = $loader->findClassPath($className);

		if (empty($classPath)) {
			throw new Exception\RuntimeException('Could not find system path for class ' . $className);
		}

		return $classPath;
	}

}
