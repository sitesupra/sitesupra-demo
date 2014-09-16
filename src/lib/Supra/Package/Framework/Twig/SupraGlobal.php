<?php

namespace Supra\Package\Framework\Twig;

use Supra\Core\Application\ApplicationManager;
use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;

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
}