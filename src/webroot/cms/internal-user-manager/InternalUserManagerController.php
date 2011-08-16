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
	protected static $defaultAction = 'root';
	
	/**
	 * @param string $namespace
	 * @param string $action
	 * @return string 
	 */
	protected function getClassName($namespace, $action)
	{
		// Normalize abc-DEF to class AbcDef so the request remains case insensitive
		$normalAction = $this->normalizeUrl($action);
		
		$class = $namespace . '\\' . $action . '\\' . $normalAction 
				. static::ACTION_CLASS_SUFFIX;
		
		return $class;
	}
}
