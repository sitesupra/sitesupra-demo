<?php

namespace Supra\Core\Routing;

use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Core\Routing\Configuration\RoutingConfiguration;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class Router implements ContainerAware
{
	protected $container;

	/**
	 * Application routes
	 *
	 * @var RouteCollection
	 */
	protected $routes;

	public function __construct()
	{
		$this->routes = new RouteCollection();
	}

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
			$route = new Route(
					$config['configuration']['prefix'] . $routeParams['pattern'],
						array_merge(
							$config['configuration']['defaults'],
							$routeParams['defaults'],
							array('controller' => $routeParams['controller'])
							)
					);

			$this->routes->add($name, $route);
		}
	}

	public function match(Request $request)
	{
		$context = new RequestContext();
		$context->fromRequest($request);

		$matcher = new UrlMatcher($this->routes, $context);

		return $matcher->match($request->getPathInfo());
	}

	public function getRouteCollection()
	{
		return $this->routes;
	}
}
