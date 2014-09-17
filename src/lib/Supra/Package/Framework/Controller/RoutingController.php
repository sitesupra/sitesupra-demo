<?php


namespace Supra\Package\Framework\Controller;

use Supra\Core\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Route;

class RoutingController extends Controller
{
	public function exportAction()
	{
		$routeCollection = $this->container->getRouter()->getRouteCollection();

		$data = array();

		foreach ($routeCollection as $name => $route) {
			/* @var $route Route */
			if ($route->getOption('frontend')) {
				$compiled = $route->compile();
				$data[$name] = array(
					'tokens' => $compiled->getTokens(),
					'variables' => $compiled->getVariables(),
					'defaults' => $route->getDefaults(),
					'requirements' => $route->getRequirements()
				);
			}
		}

		$content = 'Supra.Url.setRoutes('.json_encode($data).');';

		return new Response($content, 200, array('Content-Type' => 'text/javascript'));
	}
}
