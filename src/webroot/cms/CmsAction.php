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
use Supra\AuditLog\AuditLogEvent;
use Supra\User\Entity\AnonymousUser;
use Supra\AuditLog\TitleTrackingItemInterface;
use Supra\Controller\Pages\Entity;
use Supra\FileStorage\Entity as FileEntity;

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
		// Used for exception debugging to see the context
		$debugRequest = $_REQUEST;
		if (isset($debugRequest['password'])) {
			$debugRequest['password'] = '******';
		}
		if (isset($debugRequest['confirm_password'])) {
			$debugRequest['confirm_password'] = '******';
		}
		
		// Handle localized exceptions
		try {
			
			$response = $this->getResponse();
			$localeId = $this->getLocale()->getId();
			
			if ($response instanceof TwigResponse) {
				$response->assign('currentLocale', $localeId);
			}
			
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

			$this->log->warn($exception, "\nRequest:\n", $debugRequest);

			/*
			 * Resource not found exceptions should be thrown to CmsController 
			 * for static json file execution, for DEVELOPEMENT only!
			 */
		}
		catch (Exception\ResourceNotFoundException $e) {
			throw $e;
		}
		catch (EntityAccessDeniedException $e) {

			// No support for not Json actions
			$response = $this->getResponse();
			if ( ! $response instanceof JsonResponse) {
				throw $e;
			}

			$response->setCode(403);
			$response->setErrorMessage('Permission to "' . $e->getPermissionName() . '" is denied.');

			$this->log->warn($e);
		}
		catch (\Exception $e) {
			// No support for not Json actions
			$response = $this->getResponse();
			if ( ! $response instanceof JsonResponse) {
				throw $e;
			}

			//TODO: Remove later. Should not be shown to user
			$response->setErrorMessage($e->getMessage());

			// Write the issue inside the log
			$this->log->error($e, "\nRequest:\n", $debugRequest);		
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
		}
		else {
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
		}
		else {
			$value = $request->getQueryValue($key);
		}

		return $value;
	}

	/**
	 * Return current locale
	 * @return Locale
	 */
	protected function getLocale()
	{
		$localeManager = ObjectRepository::getLocaleManager($this);
		$locale = $localeManager->getCurrent();

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
				$this->user = new AnonymousUser();
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

	/**
	 * Write to audit log
	 *
	 * @param string $action
	 * @param string $message
	 * @param string $item
	 * @param int $level 
	 */
	protected function writeAuditLog($action, $message, $item = null, $level = AuditLogEvent::INFO)
	{
		$auditLog = ObjectRepository::getAuditLogger($this);
		$user = $this->getUser();

		if (is_object($item)) {
			$itemString = null;
			if ($item instanceof Entity\PageLocalization) {
				$itemString = 'page ';
			} else if ($item instanceof Entity\TemplateLocalization) {
				$itemString = 'template ';
			} else if ($item instanceof FileEntity\Image) {
				$itemString = 'image ';
			} else if ($item instanceof FileEntity\Folder) {
				$itemString = 'folder ';
			} else if ($item instanceof FileEntity\Abstraction\File) {
				$itemString = 'file ';
			}

			if ($item instanceof TitleTrackingItemInterface) {
				$originalTitle = $item->getOriginalTitle();

				$itemTitle = $item->getTitle();
				if ( ! is_null($originalTitle) && ($originalTitle != $itemTitle)) {
					$itemString .= "'{$itemTitle}' (title changed from '{$originalTitle}')";
				} else {
					$itemString .= "'" . $itemTitle . "'";
				}
			}

			$item = $itemString;
		}

		$message = str_replace('%item%', $item, $message);
		$message = ucfirst($message);

		$auditLog->info($this, $action, $message, $user, array());
	}
}
