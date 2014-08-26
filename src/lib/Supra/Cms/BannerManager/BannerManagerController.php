<?php

namespace Supra\Cms\BannerManager;

use Supra\Controller\SimpleController;
use Supra\Authorization\AuthorizedControllerInterface;
use Supra\User\Entity\AbstractUser;
use Supra\Response\JsonResponse;
use Supra\Request\RequestInterface;
use Supra\Controller\DistributedController;

/**
 * Banner Manager controller
 */
class BannerManagerController extends DistributedController
{
	protected $defaultAction = 'root';
}
