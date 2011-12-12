<?php

namespace Supra\Cms\CrudManager;

use Supra\Controller\DistributedController;

/**
 * Crud controller
 */
class CrudManagerController extends DistributedController
{

	/**
	 * Default action when no action is provided
	 * @var string
	 */
	protected $defaultAction = 'root';

}