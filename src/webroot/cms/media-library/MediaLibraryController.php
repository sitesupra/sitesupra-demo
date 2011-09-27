<?php

namespace Supra\Cms\MediaLibrary;

use Supra\Controller\DistributedController;
use Supra\Controller\Exception\ResourceNotFoundException;
use Supra\Log\Log;
use Supra\Authorization\AuthorizedControllerInterface;
use Supra\User\Entity\Abstraction\User;

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

