<?php

namespace Supra\Cms\Dashboard\Stats;

use Supra\Cms\CmsAction;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Statistics\GoogleAnalytics\GoogleAnalyticsDataProvider;
use Supra\Cms\Dashboard\DasboardAbstractAction;

class StatsAction extends DasboardAbstractAction
{

	//const STATS_PERIOD_DAYS = 2;
	const STATS_PERIODS = 2;
	const STATS_INCLUDE_TODAY = false;
	
	const DAY_PERIOD = 86400;
	const WEEK_PERIOD = 604800;
	
	protected $provider;
	
	
	/**
	 * 
	 */
	public function __construct()
	{
		$this->provider = new GoogleAnalyticsDataProvider();
	}
	
	/**
	 * 
	 */
	public function statsAction()
	{
		$siteId = ObjectRepository::getIniConfigurationLoader($this)->getValue('system', 'id', null);		
		
		//@TODO: create solution for Supra7 overall, not Portal only
		if (empty($siteId)) {
			$this->getResponse()
				->setResponseData(array());
			
			return;
		}
		
		$responseData = false;
		
		$writeableIni = ObjectRepository::getIniConfigurationLoader($this);
		$profileId = $writeableIni->getValue('google_analytics', 'profile_id', null);
		
		if ( ! empty($profileId)) {
			$cacheKey = get_class($this) . $profileId . '_stats';
			$cache = ObjectRepository::getCacheAdapter($this);
			$responseData = $cache->fetch($cacheKey);		
		}
		
		$cacheResults = false;

		if ($responseData === false) {
			$isAuthenticated = $this->provider->isAuthenticated();

			$responseData = array(
				'profile_id' => $profileId,
				'is_authenticated' => $isAuthenticated,
				'stats' => null,
				'authorization_url' => $this->provider->getAuthAdapter()->createAuthorizationUrl($siteId),
			);

			if ( ! empty($profileId)) {
				if ($isAuthenticated) {

					$this->provider->setProfileId($profileId);

					$stats = $this->loadStatsCollection();
					$responseData['stats'] = $stats;
					
					$cacheResults = true;
				}
			}
		}
		
		if ($cacheResults) {
			$cache->save($cacheKey, $responseData, 60*60*24);
		}
		
		$this->getResponse()
				->setResponseData($responseData);
	}
	
	/**
	 * Google Profile Id save action
	 */
	public function saveAction()
	{
		$this->isPostRequest();
		$request = $this->getRequest();
		/* @var $request \Supra\Request\HttpRequest */

		$saved = false;
		
		$profileId = $request->getPostValue('profile_id');
		if ( ! empty($profileId)) {
			$writeableIni  = ObjectRepository::getIniConfigurationLoader($this);
			if ( ! $writeableIni instanceof \Supra\Configuration\Loader\WriteableIniConfigurationLoader) {
				throw new \RuntimeException('Statistics save action requires Writeable ini loader instance');
			}
			
			$writeableIni->setValue('google_analytics', 'profile_id', $profileId);
			$writeableIni->write();
			
			$saved = true;
		}
		
		$this->getResponse()
				->setStatus($saved);		
	}
	
	/**
	 * List available profiles
	 */
	public function profilesAction()
	{
		// use refresh token as a part of cache key
		$refreshToken = $this->provider->getRefreshToken();
		
		$profiles = false;
		
		if ( ! empty($refreshToken)) {
			$cacheKey = get_class($this) . $refreshToken;
			$cache = ObjectRepository::getCacheAdapter($this);
		
			$profiles = $cache->fetch($cacheKey);
		}
		
		if ($profiles === false) {
			
			$profiles = array();

			$isAuthenticated = $this->provider->isAuthenticated();

			if ($isAuthenticated) {
				$profilesList =  $this->provider->listProfiles();

				foreach($profilesList as $profile) {
					$profiles[] = array(
						'id' => $profile['profileId'],
						'name' => $profile['profileName'],
					);
				}
			}
			
			$cache->save($cacheKey, $profiles, 600);
		}

		$this->getResponse()
				->setResponseData($profiles);
	}
	
	/**
	 * @return array
	 */
	protected function loadStatsCollection()
	{
		$now = time();
		$period = array($now - 6048000, $now);
		
		$statistics = array(
			'keywords' => array(),
			'sources' => array(),
			'visitors' => array(),
		);
		
		$keywords = $this->provider->getTopKeywords($period);
		$sources = $this->provider->getTopSources($period);
		
		
		
		return $statistics;
	}
}