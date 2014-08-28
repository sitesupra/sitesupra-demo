<?php

namespace Supra\Cms;

use Supra\Controller\DistributedController;
use Supra\Controller\Exception\ResourceNotFoundException;
use Supra\Log\Log;
use Supra\Uri\Path;

/**
 * Main CMS controller
 */
class CmsController extends DistributedController
{
	const ACTION_CLASS_SUFFIX = 'Controller';
	
	/**
	 * Moved to CmsPageEventArgs
	 * @deprecated
	 */
	const EVENT_POST_PAGE_PUBLISH = 'postPagePublish';
	
	/**
	 * Moved to CmsPageEventArgs
	 * @deprecated
	 */
	const EVENT_POST_PAGE_DELETE = 'postPageDelete';
	
	/**
	 * Page manager is the default action
	 * @var string
	 */
	protected $defaultAction = 'content-manager';
	
	/**
	 * @TODO: Extended with DEV static files, will be removed later
	 */
	public function execute()
	{
		$actionList = $this->getRequest()
				->getActions();
		
		$actionString = $this->getRequest()
				->getActionString('/');
		
		try {
			parent::execute();
		} catch (ResourceNotFoundException $notFound) {
			
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
				
				$this->log->warn("DEVELOPMENT: Will use static data file for action {$actionString} because of ResourceNotFoundException exception '{$notFound->getMessage()}'");
				
				ob_start();
				require_once($path);
				$output = ob_get_clean();
				
				$this->response->output($output);
			} else {
				throw $notFound;
			}
		}
	}
	
	/**
	 * Goes through all defined applications to find match and route the request to external applications as well
	 * @param Path $path
	 * @return string
	 */
	protected function getFullClassName(Path $path)
	{
		$applications = CmsApplicationConfiguration::getInstance()
				->getArray();
		
		$maxDepth = null;
		/* @var $applicationMatch ApplicationConfiguration */
		$applicationMatch = null;
		$applicationPathMatch = null;
		
		foreach ($applications as $application) {
			/* @var $application ApplicationConfiguration */
			$applicationPath = new Path($application->url);
			$pathDepth = $applicationPath->getDepth();
			
			if ($pathDepth > $maxDepth && $path->startsWith($applicationPath)) {
				if ( ! is_null($application->classname)) {
					$applicationMatch = $application;
					$applicationPathMatch = $applicationPath;
					$maxDepth = $pathDepth;
				}
			}
		}
		
		if ( ! is_null($applicationMatch)) {
			$path->setBasePath($applicationPathMatch);
			$classname = $applicationMatch->classname;
			
			return $classname;
		}
		
		// Default router
		return parent::getFullClassName($path);
	}

}
