<?php

namespace Supra\Cms\Settings;

use Supra\Controller\DistributedController;

/**
 * Settings application controller
 */
class SettingsController extends DistributedController
{
	/**
	 * Default action when no action is provided
	 * @var string
	 */
	protected $defaultAction = 'root';
}