<?php

namespace Supra\Cms\ContentManager;

use Supra\Controller\DistributedController;
use Supra\Controller\NotFoundException;

/**
 * Main CMS controller
 */
class CmsController extends DistributedController
{
	/**
	 * Default action when no action is provided
	 * @var string
	 */
	protected static $defaultAction = 'root';
	
	/**
	 * @return string
	 */
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
	
	/**
	 * Extended with DEV static files
	 */
	public function execute()
	{
		$actionList = $this->getRequest()
				->getActions();
		
		try {
			parent::execute();
		} catch (NotFoundException $notFound) {
			
			$fileName = array_pop($actionList);
			$extension = strstr($fileName, '.');
			if ($extension != '.json') {
				throw $notFound;
			}
			
			array_push($actionList, 'dev', $fileName);
			$path = implode(DIRECTORY_SEPARATOR, $actionList);
			$path = __DIR__ . DIRECTORY_SEPARATOR . $path;
			
			// Don't allow any hacks
			if (strpos($path, '..') !== false) {
				throw $notFound;
			}
			
			if (file_exists($path)) {
				$this->response->output(file_get_contents($path));
			} else {
				throw $notFound;
			}
		}
	}

}
