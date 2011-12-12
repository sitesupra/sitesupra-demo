<?php

namespace Supra\Cms\CrudManager\Root;

use Supra\Cms\CmsAction;
use Supra\Request;
use Supra\Response;

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
		$this->getResponse()->outputTemplate('crud-manager/root/root.html.twig');
	}

}