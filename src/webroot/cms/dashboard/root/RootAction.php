<?php

namespace Supra\Cms\Dashboard\Root;

use Supra\Cms\CmsAction;
use Supra\Request;


class RootAction extends CmsAction
{
	
	public function createResponse(Request\RequestInterface $request)
	{
		return $this->createTwigResponse();
	}
	
	public function indexAction()
	{
		$this->getResponse()
				->outputTemplate('dashboard/root/root.html.twig');
	}

}