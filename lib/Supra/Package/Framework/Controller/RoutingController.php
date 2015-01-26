<?php

/*
 * Copyright (C) SiteSupra SIA, Riga, Latvia, 2015
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 */


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
