<?php

namespace Supra\Cms\Logout;

use Supra\Controller\DistributedController;
use Supra\Controller\Exception\ResourceNotFoundException;
use Supra\Log\Log;

/**
 * Logout controller
 */
class LogoutController extends DistributedController
{
	/**
	 * Default action when no action is provided
	 * @var string
	 */
	protected static $defaultAction = 'root';
}

