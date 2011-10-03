<?php

namespace Supra\Loader\Exception;

/**
 * Class not found exception
 * 
 */
class ClassNotFound extends \RuntimeException implements LoaderException
{

	/**
	 * @param string $path
	 */
	public function __construct($className)
	{
		$message = "Class $className has not been found.";
		parent::__construct($message);
	}

}