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

namespace Supra\Package\Cms\Controller;

use Symfony\Component\HttpFoundation\Request;
use Supra\Core\HttpFoundation\SupraJsonResponse;
use Supra\Core\Controller\Controller;

class PagesController extends Controller
{
	protected $application = 'content-manager';

	public function indexAction()
	{
		return $this->renderResponse('index.html.twig');
	}

	/**
	 * @FIXME: returns fake data, granting edit/publish action permissions
	 *			for anything passed in.
	 * 
	 * @return SupraJsonResponse
	 */
	public function checkPermissionsAction(Request $request)
	{
		$permissions = array();

		foreach ($request->request->get('_check-permissions', array()) as $key => $item) {
			$permissions[$key] = array(
				'edit_page'	=> true,
				'supervise_page' => true,
			);
		}

		$response = new SupraJsonResponse();
		$response->setPermissions($permissions);

		return $response;
	}
}
