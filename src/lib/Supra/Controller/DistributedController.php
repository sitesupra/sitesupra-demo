<?php

namespace Supra\Controller;

use Supra\Request;
use Supra\Response;
use Supra\Authorization\AuthorizedControllerInterface;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Loader\Loader;
use Supra\Uri\Path;

/**
 * Simple HTTP controller based on subcontrollers
 */
abstract class DistributedController extends ControllerAbstraction
{
	/**
	 * Suffix to append to action classes
	 */
	const ACTION_CLASS_SUFFIX = 'Action';
	
	/**
	 * Default action when no action is provided
	 * @var string
	 */
	protected $defaultAction = 'Index';
	
	/**
	 * Must provide the base namespace, taken from the current class name
	 * @return string
	 */
	public function getBaseNamespace()
	{
		$className = get_class($this);
		$lastBackslash = strrpos($className, '\\');
		$namespace = '';
		
		if ($lastBackslash !== false) {
			$namespace = substr($className, 0, $lastBackslash);
		}
		
		return $namespace;
	}

	/**
	 * Executes the controller
	 */
	public function execute()
	{
		$request = $this->getRequest();
		
		if ( ! $request instanceof Request\HttpRequest) {
			throw new Exception\NotImplementedException("Not http requests are not supported yet");
		}
		
		$path = $request->getPath();
		
		// Finding class NAMESPACE\AbcDef\AbcDefAction
		$class = $this->getFullClassName($path);

		\Log::debug('Class: ', $class);

		if ( ! Loader::classExists($class)) {
			throw new Exception\ResourceNotFoundException("Action '$path' was not found (class '$class')");
		}
		
		$actionController = FrontController::getInstance()->runController($class, $request);
		
		// Not using $response because it might be rewritten
		$actionController->getResponse()->flushToResponse($this->getResponse());
	}
	
	/**
	 * @param string $url
	 * @return string
	 */
	protected function normalizeUrl($url)
	{
		$url = explode('-', $url);
		$url = array_map('mb_strtolower', $url);
		$url = array_map('ucfirst', $url);
		$url = implode($url);
		
		return $url;
	}
	
	/**
	 * @param string $namespace
	 * @param string $action
	 * @return string 
	 */
	protected function getClassName($namespace, $action)
	{
		// Normalize abc-DEF to class AbcDef so the request remains case insensitive
		$action = $this->normalizeUrl($action);
		
		$class = $namespace . '\\' . $action . '\\' . $action 
				. static::ACTION_CLASS_SUFFIX;
		
		return $class;
	}
	
	protected function getFullClassName(Path $path)
	{
		$request = $this->getRequest();
		
		$action = $request->getActions(1);

		\Log::debug('Action: ', $action);
		$baseAction = $this->defaultAction;

		if ( ! empty($action)) {
			$baseAction = $action[0];
			$path->setBasePath(new \Supra\Uri\Path($baseAction));
		}
		
		$baseNamespace = $this->getBaseNamespace();
		$class = $this->getClassName($baseNamespace, $baseAction);
		
		return $class;
	}

}