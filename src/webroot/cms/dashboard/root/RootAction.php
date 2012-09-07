<?php

namespace Supra\Cms\Dashboard\Root;

use Supra\Cms\CmsAction;
use Supra\Request;
use Supra\Response\TwigResponse;

class RootAction extends CmsAction
{

	public function createResponse(Request\RequestInterface $request)
	{
		return $this->createTwigResponse();
	}

	public function indexAction()
	{
		$showWelcome = $this->getRequestInput()->get('welcome', false);
		$showSiteList = $this->getRequestInput()->get('site_list', false);

		/* @var $response \Supra\Response\TwigResponse */
		$response = $this->getResponse();

		$response->assign('show_welcome', $showWelcome);
		$response->assign('show_site_list', $showSiteList);

		$response->outputTemplate('dashboard/root/root.html.twig');
	}

}