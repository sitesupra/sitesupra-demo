<?php

namespace Supra\Cms\CrudManager\Root;

use Supra\Controller\SimpleController;
use Supra\Request;
use Supra\Response;

/**
 * Root action, returns initial HTML
 * @method PageRequest getRequest()
 * @method Response\TwigResponse getResponse()
 */
class RootAction extends SimpleController
{

	/**
	 * @param Request\RequestInterface $request
	 * @return Response\ResponseInterface 
	 */
	public function createResponse(Request\RequestInterface $request)
	{
		$response = SimpleController::createResponse($request);

		return $response;
	}

	/**
	 * Method returning manager initial HTML
	 */
	public function indexAction()
	{
		$output = file_get_contents(dirname(__DIR__) . '/index.html');
		$this->getResponse()->output($output);
	}

}