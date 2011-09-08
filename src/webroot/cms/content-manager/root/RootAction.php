<?php

namespace Supra\Cms\ContentManager\Root;

use Supra\Controller\SimpleController;
use Supra\Cms\ContentManager\PageManagerAction;
use Supra\Request;
use Supra\Response;

/**
 * Root action, returns initial HTML
 */
class RootAction extends PageManagerAction
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
		//TODO: introduce some template engine
		$output = file_get_contents(dirname(__DIR__) . '/index.html');
		
		$pageId = $this->getInitialPageId();
		$pageId = json_encode($pageId);
		
		// TODO: simple regexps to add some dynamic content to the index.html and don't break the static version
//		$output = preg_replace('/DYNAMIC_PATH = \'\/cms\';/', 'DYNAMIC_PATH = \'/admin\';', $output);
		$output = preg_replace('/\'id\': 2/', "'id': $pageId", $output);
		
		$this->getResponse()->output($output);
	}
}
