<?php

namespace Supra\Cms\ContentManager\medialibrary;

use Supra\Controller\SimpleController;

/**
 *
 */
class MediaLibraryAction extends SimpleController
{

	/**
	 * @return string
	 */
	public function medialibraryAction()
	{
		//TODO: Must get real controller, should be bound somehow
		$controller = new \Project\Pages\Controller();

		//FIXME: hardcoded now
		$locale = 'en';
		$media = \Supra\Controller\Pages\Entity\Layout::MEDIA_SCREEN;

		// Create special request
		$request = new \Supra\Controller\Pages\Request\RequestEdit($locale, $media);

		$response = $controller->createResponse($request);
		$controller->prepare($request, $response);

		// Entity manager
		$em = $request->getDoctrineEntityManager();
		$controller->execute($request);

		// TODO: json encoding must be already inside the manager action response object
//		$this->response->output(json_encode($array));
	}

	public function listAction()
	{
		1 + 1;
	}

	public function createAction()
	{
		1 + 1;
	}

	public function saveAction()
	{
		1 + 1;
	}

	public function deleteAction()
	{
		1 + 1;
	}

}
