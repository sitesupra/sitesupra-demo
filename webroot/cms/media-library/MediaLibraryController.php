<?php

namespace Supra\Cms\MediaLibrary;

use Supra\Controller\DistributedController;

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

