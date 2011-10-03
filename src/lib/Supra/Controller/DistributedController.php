<?php

namespace Supra\Controller;

use Supra\Request;
use Supra\Response;
use Supra\Authorization\AuthorizedControllerInterface;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Loader\Loader;

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
		//FIXME: make it work for CLI request as well
		$request = $this->getRequest();
		
		if ( ! $request instanceof Request\HttpRequest) {
			throw new Exception\NotImplementedException("Not http requests are not supported yet");
		}
			
		$action = $request->getActions(1);

		\Log::debug('Action: ', $action);
		$baseAction = $this->defaultAction;

		if ( ! empty($action)) {
			$baseAction = $action[0];
			$request->getPath()->setBasePath(new \Supra\Uri\Path($baseAction));
		}
		
		// Finding class NAMESPACE\AbcDef\AbcDefAction
		$baseNamespace = $this->getBaseNamespace();
		$class = $this->getClassName($baseNamespace, $baseAction);

		\Log::debug('Class: ', $class);

		if ( ! class_exists($class)) {
			throw new Exception\ResourceNotFoundException("Action '$baseAction' was not found (class '$class')");
		}
		
		$actionController = Loader::getClassInstance($class, 'Supra\Controller\ControllerInterface');
		
		FrontController::getInstance()->runController($actionController, $request);
		
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

}