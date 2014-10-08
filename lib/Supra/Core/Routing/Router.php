<?php

namespace Supra\Core\Routing;

use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Core\Routing\Configuration\RoutingConfiguration;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class Router implements ContainerAware
{
	/**
	 * @var \Supra\Core\DependencyInjection\ContainerInterface
	 */
	protected $container;

	/**
	 * Application routes
	 *
	 * @var RouteCollection
	 */
	protected $routeCollection;

	/**
	 * Routes that are added, but not yet loaded to the container
	 *
	 * @var array
	 */
	protected $routes = array();

	/**
	 * @var RequestContext
	 */
	protected $context;

	/**
	 * @var \Symfony\Component\Routing\Generator\UrlGeneratorInterface
	 */
	protected $generator;

	public function setContainer(ContainerInterface $container)
	{
		$this->container = $container;
	}

	public function loadConfiguration($config)
	{
		if (!is_array($config)) {
			$config = $this->container['config.universal_loader']->load($config);
		}

		$processor = new Processor();
		$definition = new RoutingConfiguration();

		$config = $processor->processConfiguration($definition, array($config));

		foreach ($config['routes'] as $name => $routeParams) {
			$this->routes[] = array(
				'name' => $name,
				'params' => $routeParams,
				'config' => $config
			);
		}
	}

	public function match(Request $request)
	{
		$context = new RequestContext();
		$context->fromRequest($request);
		$this->context = $context;

		$matcher = new UrlMatcher($this->getRouteCollection(), $context);

		return $matcher->match($request->getPathInfo());
	}

	public function getRouteCollection()
	{
		//@todo: maybe consider route collection as frozen
		if ($this->routeCollection) {
			return $this->routeCollection;
		}

		$routeCollection = new RouteCollection();

		foreach ($this->routes as $route) {
			$pattern = $this->container->replaceParametersScalar(
				$route['config']['configuration']['prefix'] . $route['params']['pattern']
			);

			$routeObj = new Route(
				$pattern,
				array_merge(
					$route['config']['configuration']['defaults'],
					$route['params']['defaults'],
					array('controller' => $route['params']['controller'])
				),
				$route['params']['requirements'],
				$route['params']['options']
			);

			$routeCollection->add($route['name'], $routeObj);
		}

		$this->routes = array();

		return $this->routeCollection = $routeCollection;
	}

	public function generate($name, $parameters = array(), $absolute = false)
	{
		return $this->getGenerator()->generate($name, $parameters, $absolute);
	}

	protected function getGenerator()
	{
		if ($this->generator) {
			return $this->generator;
		}

		$generator = new UrlGenerator($this->getRouteCollection(), $this->getContext());

		return $this->generator = $generator;
	}

	protected function getContext()
	{
		if ($this->context) {
			return $this->context;
		}

		$context = new RequestContext();

		return $this->context = $context;
	}
}
