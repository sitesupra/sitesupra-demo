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

		$iniConfiguration = ObjectRepository::getIniConfigurationLoader($this);

		$sectionName = 'google_analytics';
		if ( ! $iniConfiguration->getSection($sectionName, false)) {
			$sectionName = 'googleAnalytics';
		}

		$accountId = $iniConfiguration->getValue($sectionName, 'account_id', false);
		if ( ! $accountId) {
			return;
		}

		$serverHttpHostAsDomainName = $iniConfiguration->getValue($sectionName, 'server_http_host_as_domain_name', false);
		$systemHostAsDomainName = $iniConfiguration->getValue($sectionName, 'system_host_as_domain_name', false);

		if ($serverHttpHostAsDomainName == true) {

			list($domainName, $port) = explode(':', $eventArgs->request->getServerValue('HTTP_HOST'));
		} else if ($systemHostAsDomainName == true) {

			$domainName = $iniConfiguration->getValue('system', 'host', false);
		} else {

			$domainName = $iniConfiguration->getValue($sectionName, 'domain_name', false);
		}

		$response = new \Supra\Response\TwigResponse($this);
		$response->assign('accountId', $accountId);
		$response->assign('domainName', $domainName);
		$response->outputTemplate('main.js.twig');

		$eventArgs->response
				->getContext()
				->addJsToLayoutSnippet('js', $response);
	}

}
