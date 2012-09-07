<?php

namespace Supra\Controller\Exception;

/**
 * MethodNotAllowedException
 */
class MethodNotAllowedException extends RuntimeException
{
	public function __construct($expectedMethod, $actualMethod = null)
	{
		parent::__construct("Method $expectedMethod expected" . ($actualMethod ? ", $actualMethod received" : ''));
	}

}
