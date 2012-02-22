<?php

namespace Supra\Loader\Strategy;

use Supra\Loader\Loader;
use Supra\Loader\Exception;

class SupraProxyLoadStrategy extends NamespaceLoaderStrategy
{

	public function convertToFilePath($classPath)
	{
		$path = str_replace('\\', '', $classPath) . '.php';
		return $path;
	}

}
