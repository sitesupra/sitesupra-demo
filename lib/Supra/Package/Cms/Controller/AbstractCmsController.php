<?php

namespace Supra\Package\Cms\Controller;

use Supra\Core\Controller\Controller;
use Symfony\Component\Security\Core\User\UserInterface;

abstract class AbstractCmsController extends Controller
{
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
}