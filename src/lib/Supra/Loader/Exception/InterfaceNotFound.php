<?php

namespace Supra\Loader\Exception;

/**
 * Interface not found exception
 * 
 */
class InterfaceNotFound extends \RuntimeException implements LoaderException
{

	/**
	 * @param string $path
	 */
	public function __construct($interface)
	{
		$message = "Class or interface $interface has not been found.";
		parent::__construct($message);
	}

}