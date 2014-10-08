<?php

namespace Supra\Package\Cms\Controller;

use Symfony\Component\HttpFoundation\Request;
use Supra\Core\Controller\Controller;
use Supra\Core\HttpFoundation\SupraJsonResponse;

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
