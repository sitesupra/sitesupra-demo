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

namespace Supra\Core\Locale\Detector;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Request parameter locale detector
 */
class ParameterDetector implements DetectorInterface
{
	/**
	 * @var string
	 */
	private $parameterName;

	/**
	 * @param string|null $parameterName Parameter name used to pass the locale ID
	 */
	public function __construct($parameterName = 'locale')
	{
		$this->parameterName = $parameterName;
	}

	/**
	 * Detects the current locale
	 *
	 * @param Request $request
	 * @param Response $response
	 * @return string
	 */
	public function detect(Request $request)
	{
		if ($request->isMethod('post')) {
			return $request->request->get($this->parameterName);
		}

		return $request->query->get($this->parameterName);
	}
}