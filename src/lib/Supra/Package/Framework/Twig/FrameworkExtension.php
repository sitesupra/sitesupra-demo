<?php

namespace Supra\Package\Framework\Twig;

use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;

class FrameworkExtension extends \Twig_Extension implements ContainerAware
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
	 * Returns the name of the extension.
	 *
	 * @return string The extension name
	 */
	public function getName()
	{
		return 'framework';
	}

}