<?php

namespace Supra\Package\Cms\Controller;

use Supra\Core\Controller\Controller;
use Supra\Core\HttpFoundation\SupraJsonResponse;
use Supra\Package\Cms\Exception\CmsException;
use Symfony\Component\Security\Core\User\UserInterface;

abstract class AbstractCmsController extends Controller
{
	/**
	 * Request array context used for JS to provide confirmation answers
	 */
	const CONFIRMATION_ANSWER_CONTEXT = '_confirmation';

	/**
	 * @return Symfony\Component\Security\Core\User\UserProviderInterface
	 */
	protected function getUserProvider()
	{
		return $this->container['security.user_provider'];
	}

	/**
	 * @return UserInterface|null
	 */
	protected function getCurrentUser()
	{
		$token = $this->container->getSecurityContext()
				->getToken();

		return $token ? $token->getUser() : null;
	}

	/**
	 * @return Supra\Core\Locale\LocaleManager
	 */
	protected function getLocaleManager()
	{
		return $this->container->getLocaleManager();
	}

	/**
	 * @return Supra\Core\Locale\Locale
	 */
	protected function getCurrentLocale()
	{
		return $this->getLocaleManager()->getCurrentLocale();
	}

	/**
	 * @return \Symfony\Component\HttpFoundation\ParameterBag
	 */
	protected function getRequestInput()
	{
		$request = $this->container->getRequest();

		return $request->isMethod('POST')
				? $request->request
				: $request->query;
	}

	/**
	 * @param string $name
	 * @param mixed $default
	 * @return mixed
	 */
	protected function getRequestParameter($name, $default = null)
	{
		return $this->getRequestInput()
				->get($name, $default);
	}

	/**
	 * @param string $iconPath
	 * @return string
	 */
	protected function resolveWebPath($path)
	{
		if (strpos($path, ':') !== false) {

			list($packageName, $pathPart) = explode(':', $path);

			$application = $this->container->getApplication();
			$resolvedName = $application->resolveName($packageName);

			// @FIXME: contains hardcode. implement as Supra method.
			foreach ($application->getPackages() as $package) {
				if ($package->getName() === $resolvedName) {
					return '/public/'
							. $package->getName()
							. '/'
							. ltrim($pathPart, '/');
				}
			}

			throw new \InvalidArgumentException("Failed to resolve package [{$packageName}].");
		}

		return $path;
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
		$request = $this->container->getRequest();

		$confirmationPool = $request->get(self::CONFIRMATION_ANSWER_CONTEXT, array());

		if (isset($confirmationPool[$id])) {
			$userAnswer = filter_var($confirmationPool[$id], FILTER_VALIDATE_BOOLEAN);

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

		$response = new SupraJsonResponse();

		$response->addPart('confirmation', array(
			'id' => $id,
			'question' => $question,
			'answer' => $answer
		));

		return $response;
	}
}