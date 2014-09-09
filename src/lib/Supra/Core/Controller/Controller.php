<?php

namespace Supra\Core\Controller;

use Supra\Core\DependencyInjection\ContainerInterface;

abstract class Controller
{
	/**
	 * DI container
	 *
	 * @var ContainerInterface
	 */
	protected $container;

	public function setContainer(ContainerInterface $container)
	{
		$this->container = $container;
	}
}