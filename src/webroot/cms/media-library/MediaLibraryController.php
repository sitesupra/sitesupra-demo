<?php

namespace Supra\Cms\MediaLibrary;

use Supra\Controller\DistributedController;
use Supra\Controller\Exception\ResourceNotFoundException;
use Supra\Log\Log;

/**
 * Media library controller
 */
class MediaLibraryController extends DistributedController
{
	/**
	 * Default action when no action is provided
	 * @var string
	 */
	protected $defaultAction = 'root';
}

