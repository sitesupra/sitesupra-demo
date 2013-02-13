<?php

namespace Supra\Statistics\GoogleAnalytics\Listener;

use Supra\Controller\Pages\Event\PostPrepareContentEventArgs;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Request\PageRequestView;
use Supra\Controller\Pages\Request\ViewRequest;
use Supra\Response\HttpResponse;
use Supra\Request\HttpRequest;
use Supra\Response\TwigResponse;

class GoogleAnalyticsListener
{

	const ADD_GOOGLE_ANALYTICS_EVENT = 'addGoogleAnalytics';

	/**
	 * @var boolean
	 */
	protected $googleAnalyticsAdded = false;

	/**
	 * @param PostPrepareContentEventArgs $eventArgs
	 */
	public function addGoogleAnalytics(PostPrepareContentEventArgs $eventArgs)
	{
		$this->doAddGoogleAnalytics($eventArgs->request, $eventArgs->response);
	}

	/**
	 * @param PostPrepareContentEventArgs $eventArgs
	 */
	public function postPrepareContent(PostPrepareContentEventArgs $eventArgs)
	{
		if ( ! ($eventArgs->request instanceof PageRequestView)) {

			\Log::debug('Skipping, not a PageRequestView.');
			return;
		}

		if ($eventArgs->request instanceof ViewRequest) {

			\Log::debug('Skipping, is a ViewRequest.');
			return;
		}

		$this->doAddGoogleAnalytics($eventArgs->request, $eventArgs->response);
	}

	/**
	 * @param HttpResponse $requestResponse
	 */
	protected function doAddGoogleAnalytics(HttpRequest $request, HttpResponse $response)
	{
		if ( ! $this->googleAnalyticsAdded) {

			$accountId = $this->getGoogleAnalyticsAccountId();
			if ( ! empty($accountId)) {
				$googleAnalyticsResponse = $this->getGoogleAnalyticsResponse($accountId, $request);

				$response->getContext()
						->addJsToLayoutSnippet('js', $googleAnalyticsResponse);
			}
			
			$accountId = $this->getConfigurableGoogleAnalyticsAccountId();
			if ( ! empty($accountId)) {
				$googleAnalyticsResponse = $this->getGoogleAnalyticsResponse($accountId, $request, false);
				$response->getContext()
						->addJsToLayoutSnippet('js', $googleAnalyticsResponse);
			}
			
		} else {

			\Log::debug('Google Analytics already added!');
		}
	}

	/**
	 * @param string $accountId
	 * @param HttpRequest $request
	 * @param HttpResponse $resposne
	 */
	protected function getGoogleAnalyticsResponse($accountId, HttpRequest $request = null, $useHostAsDomainParameter = true)
	{
		$googleAnalyticsResponse = new TwigResponse($this);

		$responseData = $this->getGoogleAnalyticsResponseData($accountId, $request, $useHostAsDomainParameter);
		foreach ($responseData as $name => $value) {
			$googleAnalyticsResponse->assign($name, $value);
		}

		$googleAnalyticsResponse->outputTemplate('main.js.twig');

		return $googleAnalyticsResponse;
	}

	/**
	 * 
	 * @param type $accountId
	 * @param type $request
	 * @return type
	 */
	protected function getGoogleAnalyticsResponseData($accountId, HttpRequest $request = null, $useHostAsDomainParameter = true)
	{
		$iniConfiguration = ObjectRepository::getIniConfigurationLoader($this);

		$sectionName = $this->getGoogleAnalyticsSectionName();

		$serverHttpHostAsDomainName = $iniConfiguration->getValue($sectionName, 'server_http_host_as_domain_name', false);
		$systemHostAsDomainName = $iniConfiguration->getValue($sectionName, 'system_host_as_domain_name', false);

		if ($serverHttpHostAsDomainName == true || ! $useHostAsDomainParameter) {

			if (empty($request)) {
				throw new Exception\RuntimeException('No HttpRequest, can not get host for GA domainName.');
			}

			list($domainName) = explode(':', $request->getServerValue('HTTP_HOST'));
		} else if ($systemHostAsDomainName == true) {
			$domainName = $iniConfiguration->getValue('system', 'host', false);
		} else {

			$domainName = $iniConfiguration->getValue($sectionName, 'domain_name', false);
		}

		$responseData = array(
			'accountId' => $accountId,
			'domainName' => $domainName,
		);

		return $responseData;
	}

	/**
	 * @return string
	 */
	protected function getGoogleAnalyticsSectionName()
	{
		$iniConfiguration = ObjectRepository::getIniConfigurationLoader($this);

		$sectionName = 'google_analytics';
		if ( ! $iniConfiguration->getSection($sectionName, false)) {
			$sectionName = 'googleAnalytics';
		}

		return $sectionName;
	}

	/**
	 * @return string | boolean
	 */
	protected function getGoogleAnalyticsAccountId()
	{
		$iniConfiguration = ObjectRepository::getIniConfigurationLoader($this);
		
		$sectionName = $this->getGoogleAnalyticsSectionName();

		$accountId = $iniConfiguration->getValue($sectionName, 'account_id', false);

		return $accountId;
	}
	
	/**
	 * @return string | null
	 */
	protected function getConfigurableGoogleAnalyticsAccountId()
	{
		$googleIni = ObjectRepository::getIniConfigurationLoader('#google');
		if ($googleIni instanceof \Supra\Configuration\Loader\WriteableIniConfigurationLoader) {
			$accountId = $googleIni->getValue('google_analytics', 'web_property_id', null);
			if ( ! empty($accountId)) {
				return $accountId;
			}
		}
		
		return null;
	}
	
}
