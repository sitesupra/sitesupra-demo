<?php

namespace Supra\Cms\ContentManager;

use Supra\Controller\DistributedController;

/**
 * Description of CmsController
 */
class Controller extends DistributedController
{
	/**
	 * Default action when no action is provided
	 * @var string
	 */
	protected static $defaultAction = 'index';
	
	public function getBaseNamespace()
	{
		return __NAMESPACE__;
	}
	
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
