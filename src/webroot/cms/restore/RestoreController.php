<?php

namespace Supra\Cms\Restore;

use Supra\Cms\CmsAction;
use Supra\Controller\SimpleController;
use Supra\Response\TwigResponse;
use Supra\Request;
use Supra\Cms\InternalUserManager\InternalUserManagerAbstractAction;
use Supra\Controller\Exception;
use Supra\Exception\LocalizedException;
use Supra\ObjectRepository\ObjectRepository;
use Supra\User\Entity\User;
use Supra\Authentication\AuthenticationPassword;
use Supra\Uri\Path;

/**
 * Restore password controller
 *
 * @method TwigResponse getResponse()
 * @author Dmitry Polovka <dmitry.polovka@videinfra.com>
 */
class RestoreController extends InternalUserManagerAbstractAction
{
	/**
	 * Minimum password length
	 */

	const MIN_PASSWORD_LENGTH = 4;

	/**
	 * 
	 */
	public function __construct()
	{
		parent::__construct();

		$this->userProvider = ObjectRepository::getUserProvider($this);
	}

	/**
	 * Overwriting JsonResponse to TwigResponse
	 * @param Request\RequestInterface $request
	 * @return TwigResponse 
	 */
	public function createResponse(Request\RequestInterface $request)
	{
		return $this->createTwigResponse();
	}

	/**
	 * @return string
	 */
	protected function getBasePath()
	{
		$request = $this->getRequest();

		$basePath = $request->getPath()
				->getBasePath(0, Path::FORMAT_BOTH_DELIMITERS);

		return $basePath;
	}

	/**
	 * Restore index action which make check and then renders form
	 */
	public function indexAction()
	{
		$response = $this->getResponse();

		$basePath = $this->getBasePath();
		$response->assign('basePath', $basePath);

		$input = $this->getRequestInput();

		if ($input->isEmpty('e') || $input->isEmpty('t') || $input->isEmpty('h')) {
			$response->redirect($basePath . 'request');
			return;
		}

		$email = $input->getValid('e', 'email');
		$time = $input->getValid('t', 'integer');
		$hash = $input->get('h');

		$user = $this->validateUser($email, $time, $hash);

		if ($user instanceof User) {

			$response->assign('email', $email);
			$response->assign('time', $time);
			$response->assign('hash', $hash);

			$response->outputTemplate('restore/form.html.twig');
			return;
		} else {
			$response->output('Expired or invalid link. Try initiating password recovery once more.');
			return;
		}
	}

	/**
	 * User requests new password
	 */
	public function requestAction()
	{
		$request = $this->getRequest();
		$response = $this->getResponse();
		$email = '';
		$errorMessage = '';

		if ($request->isPost()) {
			$input = $request->getPost();
			$email = $input->get('email');

			if ($input->isEmpty('email')) {
				$errorMessage = 'No email address passed';
			} elseif ( ! $input->isValid('email', 'email')) {
				$errorMessage = 'Email address not valid';
			} else {

				$email = $input->getValid('email', 'email');
				$user = $this->getRequestedUser($email);

				if ( ! $user instanceof User) {
					$errorMessage = 'User with such email address is not found';
				} else {

					$this->sendPasswordChangeLink($user);

					$response->outputTemplate('restore/request.success.html.twig');
					return;
				}
			}
		}

		$response->assign('email', $email);
		$response->assign('errorMessage', $errorMessage);

		$response->outputTemplate('restore/request.html.twig');
	}

	/**
	 * Actual change password action
	 * @return type 
	 */
	public function changepasswordAction()
	{
		$this->isPostRequest();
		$input = $this->getRequestInput();

		$response = $this->getResponse();

		// Assign parameters back to template
		$email = $input->getValid('e', 'email');
		$time = $input->getValid('t', 'integer');
		$hash = $input->get('h');

		$response->assign('email', $email);
		$response->assign('time', $time);
		$response->assign('hash', $hash);

		$plainPassword = $input->get('password');
		$confirmPassword = $input->get('confirm_password');

		// Check password match
		if ($plainPassword !== $confirmPassword) {
			$response->assign('errorMessage', 'Passwords do not match');
			$response->outputTemplate('restore/form.html.twig');

			return;
		}

		// Don't need anymore
		unset($confirmPassword);

		$passwordLength = strlen($plainPassword);
		$password = new AuthenticationPassword($plainPassword);

		// TODO: password policy should be configurable for user provider
		// check password lenght
		if ($passwordLength < self::MIN_PASSWORD_LENGTH) {
			$response->assign('errorMessage', 'Passwords length should be ' . self::MIN_PASSWORD_LENGTH . ' or more characters');
			$response->outputTemplate('restore/form.html.twig');

			return;
		}

		$user = $this->validateUser($email, $time, $hash);

		if (is_null($user)) {
			$response->output('errorMessage', 'Something went wrong. Try to request new link.');

			return;
		}

		$user->resetSalt();

		$userProvider = ObjectRepository::getUserProvider($this);

		$userProvider->credentialChange($user, $password);
		$userProvider->updateUser($user);

		$response->redirect('/' . SUPRA_CMS_URL . '/login/');
	}

	public function execute()
	{
		// Handle localized exceptions
		try {
			parent::execute();
		} catch (LocalizedException $exception) {

			// No support for not Json actions
			$response = $this->getResponse();
			if ( ! $response instanceof HttpResponse) {
				throw $exception;
			}

			//TODO: should use exception "message" at all?
			$message = $exception->getMessage();
			$messageKey = $exception->getMessageKey();

			if ( ! empty($messageKey)) {
				$message = '{#' . $messageKey . '#}';
				$response->output($messageKey);
			}

			$response->output($message);
		} catch (\Exception $e) {
			$response = $this->getResponse();
			if ( ! $response instanceof HttpResponse) {
				throw $e;
			}

			//TODO: Remove later. Should not be shown to user
			$response->output($e->getMessage());
		}
	}

	/**
	 * @return User
	 */
	private function validateUser($email, $time, $hash)
	{
		$user = $this->getRequestedUser($email);

		// find user
		if (empty($user)) {
			return null;
		}

		$result = $this->validateHash($user, $time, $hash);

		if ( ! $result) {
			return null;
		}

		return $user;
	}

	/**
	 * @param string $email
	 * @return User
	 */
	private function getRequestedUser($email)
	{
		//$repo = $this->entityManager->getRepository(User::CN());
		//TODO: should it search by email or login?
		//$user = $repo->findOneByEmail($email);
		$user = $this->userProvider
				->findUserByEmail($email);

		return $user;
	}

	/**
	 * Validates hash
	 * @param string User $user
	 * @param string $time
	 * @param string $hash
	 * @return boolean
	 */
	private function validateHash(User $user, $time, $hash)
	{
		// Request valid 24h
		if (strtotime('+1 day', $time) < time()) {
			return false;
		}

		$generatedHash = $this->userProvider->generatePasswordRecoveryHash($user, $time);

		if ($generatedHash === $hash) {
			return true;
		}

		return false;
	}

}
