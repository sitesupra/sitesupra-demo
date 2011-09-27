<?php

namespace Supra\Cms\InternalUserManager;

use Supra\Controller\DistributedController;
use Supra\Controller\Exception\ResourceNotFoundException;
use Supra\Log\Log;
use Supra\Authorization\AuthorizedControllerInterface;
use Supra\Authorization\AuthorizedAction;
use Supra\Authorization\AuthorizedControlerExecuteAction;
use Supra\User\Entity\Abstraction\User;
use Supra\ObjectRepository\ObjectRepository;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;

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
