<?php

namespace Supra\Cms\Dashboard\Root;

use Supra\Cms\CmsAction;
use Supra\Request;
use Supra\Response\TwigResponse;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Event\PostPrepareContentEventArgs;

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
		
		$eventManager =  ObjectRepository::getEventManager($this);
		$postPagePrepareEventArgs = new PostPrepareContentEventArgs();
		$postPagePrepareEventArgs->request = $this->getRequest();
		$postPagePrepareEventArgs->response = $this->getResponse();
		$eventManager->fire(\Supra\Statistics\GoogleAnalytics\Listener\GoogleAnalyticsListener::ADD_GOOGLE_ANALYTICS_EVENT, $postPagePrepareEventArgs);

		$response->outputTemplate('dashboard/root/root.html.twig');
	}

}