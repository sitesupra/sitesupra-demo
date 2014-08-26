<?php

namespace Supra\Cms;

use Supra\Loader\Strategy\NamespaceLoaderStrategy;

/**
 * Namespace loader strategy with added autoloader recognition
 */
class CmsNamespaceLoaderStrategy extends NamespaceLoaderStrategy
{
	/**
	 * Special class-path mapping function for CMS
	 * @param string $classPath
	 * @return string
	 */
	public function convertToFilePath($classPath)
	{
		// Take out class name, it won't be transformed into path
		$classPathParts = explode('\\', $classPath);
		$className = array_pop($classPathParts);
		$filePath = '';
		
		if ( ! empty($classPathParts)) {
			$classPath = implode('\\', $classPathParts);
			// Transform the path, UpperCamelCase replaced with hyphen-style
			$filePath = preg_replace('/([^\\\\])([A-Z])/', '$1-$2', $classPath);
			$filePath = strtolower($filePath);
			$filePath = str_replace('\\', DIRECTORY_SEPARATOR, $filePath);
			$filePath = $filePath . DIRECTORY_SEPARATOR;
		}
		
		// Add class name back to path, add PHP extension
		$filePath = $filePath . $className . '.php';
		
		return $filePath;
	}
	
}
