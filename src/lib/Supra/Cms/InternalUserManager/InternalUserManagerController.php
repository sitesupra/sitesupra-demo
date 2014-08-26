<?php

namespace Supra\Cms\InternalUserManager;

use Supra\Controller\DistributedController;

/**
 */
class InternalUserManagerController extends DistributedController
{
	/** 
	 * Default action when no action is provided
	 * @var string
	 */
	protected $defaultAction = 'root';
}
