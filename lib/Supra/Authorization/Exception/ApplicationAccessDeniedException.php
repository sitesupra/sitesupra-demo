<?php

namespace Supra\Authorization\Exception;

class ApplicationAccessDeniedException extends AccessDeniedException
{
	public function __construct($user, $controller) 
	{
		parent::__construct($user, $controller, null);
	}
}
