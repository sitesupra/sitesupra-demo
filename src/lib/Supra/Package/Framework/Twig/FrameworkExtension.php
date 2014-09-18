<?php

namespace Supra\Package\Framework\Twig;

use Supra\Controller\FrontController;
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
			new \Twig_SimpleFunction('supra_path', array($this, 'getSupraPath')),
			new \Twig_SimpleFunction('controller', array($this, 'renderController'), array('is_safe' => array('html')))
		);
	}

	public function getSupraPath($name, $params = array())
	{
		return $this->container->getRouter()->generate($name, $params);
	}

	public function renderController($name)
	{
		//@todo: parameters support
		//@todo: less ugly controller resolver, route this through kernel
		//@todo: whatever, rewrite this completely
		$front = FrontController::getInstance();
		$controller = $front->parseControllerName($name);
		$action = $controller['action'].'Action';
		$controller = $controller['controller'];

		$controllerObject = new $controller();
		$controllerObject->setContainer($this->container);

		return call_user_func(array($controllerObject, $action))->getContent();
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