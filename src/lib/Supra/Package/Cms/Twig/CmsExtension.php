<?php

namespace Supra\Package\Cms\Twig;

use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;

class CmsExtension extends \Twig_Extension implements ContainerAware
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
		return 'cms';
	}

	public function getFunctions()
	{
		return array(
			new \Twig_SimpleFunction('supra_cms_css_pack', array($this, 'buildCssPack')),
			new \Twig_SimpleFunction('supra_cms_js_pack', array($this, 'buildJsPack')),
		);
	}

	public function buildCssPack()
	{
		//@todo: caching here
		return $this->container->getRouter()->generate('cms_css_pack');
	}

	public function buildJsPack()
	{
		//@todo: caching here
		return $this->container->getRouter()->generate('cms_js_pack');
	}

}