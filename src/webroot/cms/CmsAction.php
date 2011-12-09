<?php

namespace Supra\Cms;

use Supra\Controller\SimpleController;
use Supra\Response\JsonResponse;
use Supra\Request;
use Supra\Controller\Exception;
use Supra\Exception\LocalizedException;
use Supra\ObjectRepository\ObjectRepository;
use Supra\User\Entity\User;
use Supra\Authentication\AuthenticationSessionNamespace;
use Supra\Authorization\Exception\EntityAccessDeniedException;
use Supra\Response\TwigResponse;

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
	 * Current (authorized) user
	 * @var User
	 */
	private $user;
	
	/**
	 * Assign current user
	 */
	public function __construct()
	{
		parent::__construct();
	}
	
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
			
			$this->log->warn($exception);
			
		/*
		 * Resource not found exceptions should be thrown to CmsController 
		 * for static json file execution, for DEVELOPEMENT only!
		 */
		} catch (Exception\ResourceNotFoundException $e) {
			throw $e;
		} catch (EntityAccessDeniedException $e) {
				
			// No support for not Json actions
			$response = $this->getResponse();
			if ( ! $response instanceof JsonResponse) {
				throw $e;
			}
			
			$response->setCode(403);
			$response->setErrorMessage('Permission to "' . $e->getPermissionName() . '" is denied.');
			
			$this->log->warn($e);
			
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
	 * @return TwigResponse
	 */
	protected function createTwigResponse()
	{
		$response = new TwigResponse(__CLASS__);
		$managerConfiguration = ObjectRepository::getApplicationConfiguration($this);
		$response->assign('manager', $managerConfiguration);

		// No request object yet. Still -- do managers need to know the current locale?
//		// Current locale ID
//		$localeId = $this->getLocale()->getId();
//		$response->assign('currentLocale', $localeId);
		
		// Locale array
		$localeList = $this->createLocaleArray();
		$response->assign('localesList', $localeList);
		
		// Used to get currently signed in user
		//TODO: think about something better...
		$response->assign('action', $this);
		
		return $response;
	}
	
	/**
	 * Creates locale array for JS
	 * @return array
	 */
	private function createLocaleArray()
	{
		$localeManager = ObjectRepository::getLocaleManager($this);
		$locales = $localeManager->getLocales();
		
		$jsLocales = array();
		
		/* @var $locale Locale */
		foreach ($locales as $locale) {
			
			$country = $locale->getCountry();
			
			if ( ! isset($jsLocales[$country])) {
				$jsLocales[$country] = array(
					'title' => $country,
					'languages' => array()
				);
			}
			
			$jsLocales[$country]['languages'][] = array(
				'id' => $locale->getId(),
				'title' => $locale->getTitle(),
				'flag' => $locale->getProperty('flag')
			);
		}
		
		$jsLocales = array_values($jsLocales);
		
		return $jsLocales;
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
	 * @return RequestData
	 */
	protected function getRequestInput()
	{
		$value = null;
		$request = $this->getRequest();
		
		if ($this->requestMethod == Request\HttpRequest::METHOD_POST) {
			$value = $request->getPost();
		} else {
			$value = $request->getQuery();
		}
		
		return $value;
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
			$requestedLocaleId = $request->getQueryValue('locale');
		}
		
		if ( ! empty($requestedLocaleId)) {
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
	
	/** 
	 * Returns object of current user
	 * @return User
	 * @throws Exception\RuntimeException if there is no current user
	 */
	public function getUser()
	{
		if (is_null($this->user)) {
			$session = ObjectRepository::getSessionManager($this)
					->getSpace('Supra\Authentication\AuthenticationSessionNamespace');
			/* @var $session AuthenticationSessionNamespace */
			
			$this->user = $session->getUser();

			if ( ! $this->user instanceof User) {
				throw new Exception\RuntimeException("User is not logged in");
			}
		}
		
		return $this->user;
	}
	
	/**
	 *
	 * @param mixed $object
	 * @param string $permissionName
	 * @return boolean
	 * @throws EntityAccessDeniedException
	 */
	protected function checkActionPermission($object, $permissionName)
	{
		$user = $this->getUser();
		
		$ap = ObjectRepository::getAuthorizationProvider($this);
		$appConfig = ObjectRepository::getApplicationConfiguration($this);
		
		if ($appConfig->authorizationAccessPolicy->isApplicationAllAccessGranted($user)) {
			return true;
		}
		
		if ($ap->isPermissionGranted($user, $object, $permissionName)) {
			return true;
		}
		
		throw new EntityAccessDeniedException($user, $object, $permissionName);
	}
}
