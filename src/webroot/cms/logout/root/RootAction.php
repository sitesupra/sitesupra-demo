<?php

namespace Supra\Cms\Logout\Root;

use Supra\Controller\SimpleController;
use Supra\Cms\ContentManager\PageManagerAction;
use Supra\Request;
use Supra\Response;

/**
 * Root action
 */
class RootAction extends SimpleController
{
	private $loginPage = '/cms/login';
	
	public function getLoginPage()
	{
		return $this->loginPage;
	}

	/**
	 * @param Request\RequestInterface $request
	 * @return Response\ResponseInterface 
	 */
	public function createResponse(Request\RequestInterface $request)
	{
		$response = SimpleController::createResponse($request);
		
		return $response;
	}
	
	public function indexAction()
	{
		$loginPage = $this->getLoginPage();
		
		if(! empty($_SESSION['user'])) {
			unset($_SESSION['user']);
		}
		
		$this->response->redirect($loginPage);
	}
}