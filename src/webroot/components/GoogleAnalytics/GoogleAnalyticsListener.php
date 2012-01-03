<?php

namespace Project\GoogleAnalytics;

use Supra\Controller\Pages\Event\PostPrepareContentEventArgs;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Request\PageRequestView;

class GoogleAnalyticsListener 
{
	const SELF_DIR_PATH = 'GoogleAnalytics/';
	
	public function postPrepareContent(PostPrepareContentEventArgs $eventArgs)
	{
		$userConfig = ObjectRepository::getIniConfigurationLoader($this);
		$accountId = $userConfig->getValue('googleAnalytics', 'account_id', false);
		if ( ! $accountId) {
			return;
		}
		
		if ( ! ($eventArgs->request instanceof PageRequestView)) {
			return;
		}
		
		$twig = ObjectRepository::getTemplateParser($this);
		$loader = new \Twig_Loader_Filesystem(SUPRA_COMPONENT_PATH . self::SELF_DIR_PATH);
	
		$jsCode = $twig->parseTemplate('main.js.twig', array('accountId' => $accountId), $loader);
		
		$eventArgs->response
				->getContext()
				->addJsToLayoutSnippet('js', $jsCode);

	}
}