<?php

namespace Supra\Statistics\GoogleAnalytics\Listener;

use Supra\Controller\Pages\Event\PostPrepareContentEventArgs;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Request\PageRequestView;

class GoogleAnalyticsListener 
{
	public function postPrepareContent(PostPrepareContentEventArgs $eventArgs)
	{
		if ( ! ($eventArgs->request instanceof PageRequestView)) {
			return;
		}
		
		$userConfig = ObjectRepository::getIniConfigurationLoader($this);
		$accountId = $userConfig->getValue('googleAnalytics', 'account_id', false);
		if ( ! $accountId) {
			return;
		}
		
		$response = new \Supra\Response\TwigResponse($this);
		$response->assign('accountId', $accountId);
		$response->outputTemplate('main.js.twig');
		
		$eventArgs->response
				->getContext()
				->addJsToLayoutSnippet('js', $response);
	}
}
