<?php

namespace Supra\Cms\InternalUserManager\Root;

use Supra\Cms\InternalUserManager\InternalUserManagerAbstractAction;
use Supra\Response\TwigResponse;
use Supra\Request\RequestInterface;

/**
 * @method TwigResponse getResponse()
 */
class RootAction extends InternalUserManagerAbstractAction
{
	/**
	 * @param RequestInterface $request
	 * @return TwigResponse
	 */
	public function createResponse(RequestInterface $request)
	{
		return $this->createTwigResponse();
	}

	/**
	 * Output the template
	 */
	public function indexAction()
	{
		$this->getResponse()->outputTemplate('internal-user-manager/root/index.html.twig');
	}
}