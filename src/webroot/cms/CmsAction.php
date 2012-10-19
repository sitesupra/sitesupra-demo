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
use Supra\Authorization\Exception\ApplicationAccessDeniedException;
use Supra\Response\TwigResponse;
use Supra\AuditLog\AuditLogEvent;
use Supra\User\Entity\AnonymousUser;
use Supra\AuditLog\TitleTrackingItemInterface;
use Supra\Controller\Pages\Entity;
use Supra\FileStorage\Entity as FileEntity;
use Supra\Cms\Exception\StopExecutionException;
use Supra\Validator\FilteredInput;
use Supra\Authorization\AccessPolicy\AuthorizationThreewayWithEntitiesAccessPolicy;
use Supra\Cms\CheckPermissions\CheckPermissionsController;
use Supra\Cms\InternalUserManager\Useravatar\UseravatarAction;
use Supra\NestedSet\Exception\CannotObtainNestedSetLock;
use Supra\Controller\Pages\Twig\TwigSupraGlobal;

/**
 * Description of CmsAction
 * @method JsonResponse getResponse()
 * @method Request\HttpRequest getRequest()
 */
abstract class CmsAction extends SimpleController
{
	/**
	 * Request array context used for JS to provide confirmation answers
	 */

	const CONFIRMATION_ANSWER_CONTEXT = '_confirmation';

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

			try {
				$response = $this->getResponse();
				$localeId = $this->getLocale()->getId();

				if ($response instanceof TwigResponse) {

					$ini = ObjectRepository::getIniConfigurationLoader($this);

					if ($ini->getValue('system', 'supraportal_site', false)) {
						$response->assign('siteTitle', $ini->getValue('system', 'host'));
					}

					$response->assign('currentLocale', $localeId);
				}

				$this->processCheckPermissions();

				parent::execute();
			} catch (\Exception $e) {
				try {
					$this->finalize($e);
				} catch (\Exception $e) {
					$this->log->error("CMS action finalize method raised exception ", $e->__toString());
				}

				throw $e;
			}

			$this->finalize();
		} catch (StopExecutionException $exception) {
			// Do nothing
			$this->log->debug("CMS action excection stopped");
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
		} catch (Exception\ResourceNotFoundException $e) {
			throw $e;
		} catch (EntityAccessDeniedException $e) {

			// No support for not Json actions
			$response = $this->getResponse();
			if ( ! $response instanceof JsonResponse) {
				throw $e;
			}

			$response->setCode(403);
			$response->setErrorMessage('You don\'t have permission to perform this action.<br />Contact your supervisor.');
			//$response->setErrorMessage('Permission to "' . $e->getPermissionName() . '" is denied.');

			$this->log->warn($e);
		} catch (CannotObtainNestedSetLock $e) {
			$response->setErrorMessage('The operation has timed out. Please try again.');
			$this->log->warn($e);
		} catch (\Exception $e) {
			// No support for not Json actions
			$response = $this->getResponse();

			$eIdentifier = md5((string) $e);

			// This will be caught by FrontController and most probably HTTP response code 500 will be sent.
			if ( ! $response instanceof JsonResponse) {
				throw $e;
			}

			//TODO: Remove later. Should not be shown to user
			$response->setErrorMessage('Error occured!' . "<br />" . 'Error reference ID: #' . $eIdentifier);

			// Write the issue inside the log
			$this->log->error('#' . $eIdentifier . ' ' . $e, "\nRequest:\n", $debugRequest);
		}
	}

	/**
	 * Finilize the request
	 */
	protected function finalize(\Exception $e = null)
	{
		
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
		
		$twig = $response->getTwigEnvironment();

		$globalHelper = new TwigSupraGlobal();
		
		$globalHelper->setRequest($this->getRequest());
		$globalHelper->setResponseContext($response->getContext());

		ObjectRepository::setCallerParent($globalHelper, $this);
		$twig->addGlobal('supra', $globalHelper);
		
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
	 * @return Request\RequestData
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
	 * Return current locale
	 * @return \Supra\Locale\Locale
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
			$userProvider = ObjectRepository::getUserProvider($this, false);

			if ( ! empty($userProvider)) {
				$this->user = $userProvider->getSignedInUser();
			}

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

			if ($permissionName == \Supra\Controller\Pages\Entity\Abstraction\Entity::PERMISSION_NAME_EDIT_PAGE) {

				if ($object instanceof Entity\PageLocalization) {

					$scheduleTime = $object->getScheduleTime();

					if ( ! empty($scheduleTime)) {
						throw new EntityAccessDeniedException($user, $object, $permissionName);
					}
				}
			}

			return true;
		}

		throw new EntityAccessDeniedException($user, $object, $permissionName);
	}

	/**
	 * @return boolean
	 */
	protected function checkApplicationAllAccessPermission()
	{
		$user = $this->getUser();

		$appConfig = ObjectRepository::getApplicationConfiguration($this);

		if ($appConfig->authorizationAccessPolicy->isApplicationAllAccessGranted($user)) {
			return true;
		}

		throw new ApplicationAccessDeniedException($user, $this);
	}

	/**
	 * Write to audit log
	 *
	 * @param string $action
	 * @param string $message
	 * @param string $item
	 * @param int $level 
	 */
	protected function writeAuditLog($message, $item = null, $level = AuditLogEvent::INFO)
	{
		$auditLog = ObjectRepository::getAuditLogger($this);
		$user = $this->getUser();

		if (is_object($item)) {
			$itemString = null;
			if ($item instanceof Entity\PageLocalization) {
				$itemString = "page ({$item->getLocale()}) ";
			} else if ($item instanceof Entity\TemplateLocalization) {
				$itemString = "template ({$item->getLocale()}) ";
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

		$auditLog->info($this, $message, $user, array());
	}

	/**
	 * Sends confirmation message to JavaScript or returns answer if already received
	 * @param string $question
	 * @param string $id
	 * @param boolean $answer by default next request is made only when "Yes"
	 * 		is pressed. Setting to null will make callback for both answers.
	 */
	protected function getConfirmation($question, $id = '0', $answer = true)
	{
		$input = $this->getRequestInput();
		$confirmationPool = $input->getChild(self::CONFIRMATION_ANSWER_CONTEXT, true);

		/* @var $confirmationPool FilteredInput */

		if ($confirmationPool->has($id)) {
			$userAnswer = $confirmationPool->getValid($id, 'boolean');

			// Any answer is OK
			if (is_null($answer)) {
				return $userAnswer;
			}

			// Match
			if ($userAnswer === $answer) {
				return $userAnswer;

				// Wrong answer, in fact JS didn't need to do this request anymore
			} else {
				throw new CmsException(null, "Wrong answer");
			}
		}

		$confirmationResponsePart = array(
			'id' => $id,
			'question' => $question,
			'answer' => $answer
		);

		$this->getResponse()
				->addResponsePart('confirmation', $confirmationResponsePart);

		throw new StopExecutionException();
	}

	private function processCheckPermissions()
	{
		$request = $this->getRequest();

		$query = $request->getQuery();

		if ($query->hasChild(CheckPermissionsController::REQUEST_KEY_CHECK_PERMISSIONS)) {

			$entitiesToQuery = $query->getChild(CheckPermissionsController::REQUEST_KEY_CHECK_PERMISSIONS);

			$result = array();

			$ap = ObjectRepository::getAuthorizationProvider($this);

			$user = $this->getUser();

			foreach ($entitiesToQuery as $entityToQuery) {

				$id = $entityToQuery[CheckPermissionsController::REQUEST_KEY_ID];
				$applicationNamespaceAlias = $entityToQuery[CheckPermissionsController::REQUEST_KEY_TYPE];

				$applicationNamespace = $ap->getApplicationNamespaceFromAlias($applicationNamespaceAlias);

				$appConfig = ObjectRepository::getApplicationConfiguration($applicationNamespace);

				$policy = $appConfig->authorizationAccessPolicy;

				if ($policy instanceof AuthorizationThreewayWithEntitiesAccessPolicy) {

					$entity = $policy->getAuthorizedEntityFromId($id);

					if ( ! empty($entity)) {
						$result[] = $policy->getPermissionStatusesForAuthorizedEntity($user, $entity);
					}
				}
			}

			$this->getResponse()
					->setResponsePermissions($result);
		}
	}

	public function getCurrentUserArray()
	{
		$response = array(
			'id' => $this->user->getId(),
			'name' => $this->user->getName(),
			'login' => $this->user->getLogin(),
			'avatar' => $this->user->getGravatarUrl(32)
		);

		/* if ($this->user->hasPersonalAvatar()) {

		  $fileStorage = ObjectRepository::getFileStorage($this);
		  $path = $fileStorage->getExternalPath();
		  $path = '/' . str_replace(array(SUPRA_WEBROOT_PATH, "\\"), array('', '/'), $path);

		  $response['avatar'] = $path . '_avatars' . DIRECTORY_SEPARATOR . $this->user->getId()
		  . '_32x32';

		  } else {
		  $sampleAvatarId = $this->user->getAvatar();
		  if ( ! is_null($sampleAvatarId)) {
		  foreach (UseravatarAction::$sampleAvatars as $sampleAvatar) {
		  if ($sampleAvatarId == $sampleAvatar['id']) {
		  $response['avatar'] = $sampleAvatar['sizes']['32x32']['external_path'];
		  }
		  }
		  }
		  } */

		return $response;
	}

}
