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
	
	/**
	 * Must provide the base namespace, taken from the current class name
	 * @return string
	 */
	public function getBaseNamespace()
	{
		$className = __CLASS__;
		$lastBackslash = strrpos($className, '\\');
		$namespace = '';
		
		if ($lastBackslash !== false) {
			$namespace = substr($className, 0, $lastBackslash);
		}
		
		return $namespace;
	}

}