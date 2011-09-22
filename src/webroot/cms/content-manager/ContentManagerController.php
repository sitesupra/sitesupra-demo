<?php

namespace Supra\Cms\ContentManager;

use Supra\Controller\DistributedController;
use Supra\Controller\Exception\ResourceNotFoundException;
use Supra\Log\Log;

/**
 * Main CMS controller
 */
class ContentManagerController extends DistributedController
{
	/**
	 * Default action when no action is provided
	 * @var string
	 */
	protected $defaultAction = 'root';
}
