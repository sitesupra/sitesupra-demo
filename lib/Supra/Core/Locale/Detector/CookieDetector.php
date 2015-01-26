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

/**
 * Cookie locale detector
 */
class CookieDetector implements DetectorInterface
{
	/**
	 * Cookie name for the current locale storage
	 * @var string
	 */
	protected $cookieName = 'supra_language';

	/**
	 * @param string|null $cookieName
	 */
	public function __construct($cookieName = 'supra_language')
	{
		$this->cookieName = $cookieName;
	}

	/**
	 * Searches for the current locale in cookies.
	 *
	 * @param \Symfony\Component\HttpFoundation\Request $request
	 * @return string
	 */
	public function detect(Request $request)
	{
		return $request->cookies->get($this->cookieName, null);
	}
}