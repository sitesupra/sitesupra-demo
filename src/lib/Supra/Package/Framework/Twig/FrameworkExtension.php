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

	public function getFunctions()
	{
		return array(
			new \Twig_SimpleFunction('supra_path', array($this, 'getSupraPath'))
		);
	}

	public function getSupraPath($name, $params = array())
	{
		return $this->container->getRouter()->generate($name, $params);
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