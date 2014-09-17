<?php

namespace Supra\Package\Framework\Twig;

use Supra\Core\Application\ApplicationManager;
use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Core\Locale\LocaleManager;

class SupraGlobal implements ContainerAware
{
	/**
	 * @var ContainerInterface
	 */
	protected $container;

	public function setContainer(ContainerInterface $container)
	{
		$this->container = $container;
	}

	/**
	 * @return ApplicationManager
	 */
	public function getApplication()
	{
		return $this->container->getApplicationManager()->getCurrentApplication();
	}

	public function getUser()
	{
		$context = $this->container->getSecurityContext();

		$token = $context->getToken();

		if (!$token) {
			return false;
		}

		$user = $token->getUser();

		return $user;
	}

	/**
	 * @return LocaleManager
	 */
	public function getLocaleManager()
	{
		return $this->container->getLocaleManager();
	}
}