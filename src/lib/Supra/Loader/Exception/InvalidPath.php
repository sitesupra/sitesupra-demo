<?php

namespace Supra\Loader\Exception;

use InvalidArgumentException;

/**
 * Invalid path exception
 */
class InvalidPath extends InvalidArgumentException implements LoaderException
{
	/**
	 * @param string $path
	 */
	public function __construct($path)
	{
		$message = "Path {$path} does not exist";
		parent::__construct($message);
	}
}