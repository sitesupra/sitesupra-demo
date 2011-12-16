<?php

namespace Supra\Cms\CrudManager\Root;

use Supra\Cms\CmsAction;
use Supra\Request;
use Supra\Response;
use Supra\Uri\Path;

/**
 * Root action, returns initial HTML
 * @method PageRequest getRequest()
 * @method Response\TwigResponse getResponse()
 */
class RootAction extends CmsAction
{
	/**
	 * Generate response object
	 * @param Request\RequestInterface $request
	 * @return Response\ResponseInterface
	 */
	public function createResponse(Request\RequestInterface $request)
	{
		return $this->createTwigResponse();
	}
	
	/**
	 * Method returning manager initial HTML
	 */
	public function indexAction()
	{
		$response = $this->getResponse();
		$requestPath = $this->getRequest()->getPath();
		$pathString = $requestPath->getFullPath(Path::FORMAT_BOTH_DELIMITERS);
		
		$response->assign('path', $pathString);
		$response->outputTemplate('crud-manager/root/root.html.twig');
	}

}