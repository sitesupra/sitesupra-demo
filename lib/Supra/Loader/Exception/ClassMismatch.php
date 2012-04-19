<?php

namespace Supra\Loader\Exception;

/**
 * Class mismatch exception
 * 
 */
class ClassMismatch extends \RuntimeException implements LoaderException
{

	/**
	 * @param string $path
	 */
	public function __construct($actualClass, $expectedClass)
	{
		$message = "Instance, implementation or descendant of $expectedClass expected, but $actualClass found";
		parent::__construct($message);
	}

}