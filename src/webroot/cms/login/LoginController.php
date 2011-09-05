<?php

namespace Supra\Cms\Login;

use Supra\Controller\DistributedController;
use Supra\Controller\Exception\ResourceNotFoundException;
use Supra\Log\Log;

/**
 * Media library controller
 */
class LoginController extends DistributedController
{
	/**
	 * Default action when no action is provided
	 * @var string
	 */
	protected static $defaultAction = 'root';
}

