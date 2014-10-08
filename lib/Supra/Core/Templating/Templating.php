<?php

namespace Supra\Core\Templating;

use Assetic\Extension\Twig\AsseticExtension;
use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Core\Templating\Loader\TemplateLoader;

class Templating implements ContainerAware
{
	/**
	 * @var ContainerInterface
	 */
	protected $container;

	/**
	 * @param ContainerInterface $container
	 */
	public function setContainer(ContainerInterface $container)
	{
		$this->container = $container;
		$this->twig->getLoader()->setContainer($container);
	}

	/**
	 * @var \Twig_Environment
	 */
	protected $twig;

	public function __construct()
	{
		//@todo: this is hardcode. we should move it to some templating component
		$loader = new TemplateLoader();

		$this->twig = new \Twig_Environment($loader);
		$this->twig->enableStrictVariables();

	}

	public function render($template, $parameters)
	{
		//@todo: this also should be refactored to some more generic way in case if we have some multiple templating engines active
		return $this->twig->render($template, $parameters);
	}

	public function addGlobal($name, $value)
	{
		//@todo: this also should be refactored to some more generic way in case if we have some multiple templating engines active
		$this->twig->addGlobal($name, $value);
	}

	public function addExtension($extension)
	{
		//@todo: this also should be refactored to some more generic way in case if we have some multiple templating engines active
		$this->twig->addExtension($extension);
	}
}
