<?php

namespace Supra\Cms;

use Supra\Controller\SimpleController;
use Supra\Response\JsonResponse;
use Supra\Request;
use Supra\Controller\Exception;
use Supra\Exception\LocalizedException;
use Supra\ObjectRepository\ObjectRepository;

/**
 * Description of CmsAction
 * @method JsonResponse getResponse()
 * @method Request\HttpRequest getRequest()
 */
abstract class CmsAction extends SimpleController
{
	/**
	 * Forced request 
	 * @var string
	 */
	private $requestMethod;
	
	/**
	 * Localized error handling
	 */
	public function execute()
	{
		// Handle localized exceptions
		try {
			parent::execute();
		} catch (LocalizedException $exception) {
			
			// No support for not Json actions
			$response = $this->getResponse();
			if ( ! $response instanceof JsonResponse) {
				throw $exception;
			}
			
			//TODO: should use exception "message" at all?
			$message = $exception->getMessage();
			$messageKey = $exception->getMessageKey();
			
			if ( ! empty($messageKey)) {
				$message = '{#' . $messageKey . '#}';
				$response->setErrorMessage($messageKey);
			}

			$response->setErrorMessage($message);
			
		/*
		 * Resource not found exceptions should be thrown to CmsController 
		 * for static json file execution, for DEVELOPEMENT only!
		 */
		} catch (Exception\ResourceNotFoundException $e) {
			throw $e;
		} catch (\Exception $e) {
			// No support for not Json actions
			$response = $this->getResponse();
			if ( ! $response instanceof JsonResponse) {
				throw $e;
			}
			
			//TODO: Remove later. Should not be shown to user
			$response->setErrorMessage($e->getMessage());
			
			// Write the issue inside the log
			$this->log->error($e);
		}
	}
	
	/**
	 * @return JsonResponse
	 */
	public function createResponse(Request\RequestInterface $request)
	{
		$response = new JsonResponse();
		
		return $response;
	}
	
	/**
	 * Mark request as POST request
	 * @throws Exception\ResourceNotFoundException if POST method is not used
	 */
	protected function isPostRequest()
	{
		if ( ! $this->getRequest()->isPost()) {
			throw new Exception\BadRequestException("Post request method is required for the action");
		}
		
		$this->requestMethod = Request\HttpRequest::METHOD_POST;
	}
	
	/**
	 * If the request parameter was sent
	 * @param string $key
	 * @return boolean
	 */
	protected function hasRequestParameter($key)
	{
		$value = $this->getRequestParameter($key);
		$exists = ($value != '');
		
		return $exists;
	}
	
	/**
	 * Tells if request value is empty (not sent or empty value)
	 * @param string $key
	 * @return boolean
	 */
	protected function emptyRequestParameter($key)
	{
		$value = $this->getRequestParameter($key);
		$empty = empty($value);
		
		return $empty;
	}
	
	/**
	 * Get POST/GET request parameter depending on the action setting
	 * @param string $key
	 * @return string
	 */
	protected function getRequestParameter($key)
	{
		$value = null;
		$request = $this->getRequest();
		
		if ($this->requestMethod == Request\HttpRequest::METHOD_POST) {
			$value = $request->getPostValue($key);
		} else {
			$value = $request->getQueryValue($key);
		}
		
		return $value;
	}
	
	/**
	 * Will return Locale object specified by request params or default
	 * @return Locale
	 */
	protected function getLocale()
	{
		$locale = null;
		$localeManager = ObjectRepository::getLocaleManager($this);
		
		$request = $this->request;
		/* @var $request HttpRequest */
		
		$requestedLocaleId = $request->getPostValue('locale');
		if (empty($requestedLocaleId)) {
			$requestedLocaleId = $this->request->getParameter('locale');
		}
		
		$requestedLocaleId = substr($requestedLocaleId, 0, strpos($requestedLocaleId, '_')); // temp workaround to remove context
		if (!empty($requestedLocaleId)) {
			$localeManager = \Supra\ObjectRepository\ObjectRepository::getLocaleManager($this);
			try {
				$locale = $localeManager->getLocale($requestedLocaleId);
			} catch (\Exception $e) {
				$this->log->error("CmsAction: locale '$requestedLocaleId' is missing for request '{$this->request->getActionString()}'");
				$locale = $localeManager->getCurrent();
			}
		} else {
			$locale = $localeManager->getCurrent(); // get default (current)
		}
		
		return $locale;
	}
	
}
