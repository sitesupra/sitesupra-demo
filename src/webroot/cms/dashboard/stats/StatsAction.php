<?php

namespace Supra\Cms\Dashboard\Stats;

use Supra\ObjectRepository\ObjectRepository;
use Supra\Statistics\GoogleAnalytics\GoogleAnalyticsDataProvider;
use Supra\Cms\Dashboard\DasboardAbstractAction;

class StatsAction extends DasboardAbstractAction
{
	/**
	 * 
	 */
	public function statsAction()
	{
		$responseData = false;
		
		$ini = ObjectRepository::getIniConfigurationLoader('#google');
		$profileId = $ini->getValue('google_analytics', 'profile_id', null);
		$profileTitle = $ini->getValue('google_analytics', 'profile_title', null);
		
		$cache = ObjectRepository::getCacheAdapter($this);
		
		if ( ! empty($profileId)) {
			$cacheKey = get_class($this) . "_{$profileId}";
			$responseData = $cache->fetch($cacheKey);		
		}

		if ($responseData === false) {

			$provider = $this->getGoogleAnalyticsProvider();
			if ( ! $provider instanceof GoogleAnalyticsDataProvider) {
				return;
			}
			
			$isAuthenticated = $provider->isAuthenticated();
			$siteId = ObjectRepository::getIniConfigurationLoader($this)->getValue('system', 'id', null);
			
			$serverName = $this->getRequest()
					->getServerValue('HTTP_HOST');
			
			$state = implode(',', array($siteId, $serverName));
			
			$responseData = array(
				'account_name' => $provider->getAuthAdapter()->getCurrentAccountName(),
				'profile_id' => $profileId,
				'profile_title' => $profileTitle,
				'is_authenticated' => $isAuthenticated,
				'authorization_url' => $provider->getAuthAdapter()
					->createAuthorizationUrl($state),
				'stats' => null,
			);

			if ( ! empty($profileId)) {

				$noStatistics = $this->getRequest()
						->getQueryValue('no_statistics', false);
				
				if ($isAuthenticated && ! $noStatistics) {
					
					$provider->setProfileId($profileId);
					
					$stats = $this->loadStatsCollection();
					$responseData['stats'] = $stats;
					
					$cache->save($cacheKey, $responseData, 60*60*24); // 24h
				}
			}
		}		
		
		$this->getResponse()
				->setResponseData($responseData);
	}
	
	/**
	 * List available profiles
	 */
	public function profilesAction()
	{
		$provider = $this->getGoogleAnalyticsProvider();
		if ( ! $provider instanceof GoogleAnalyticsDataProvider) {
			return null;
		}
		
		$profiles = $this->loadUserProfiles($provider);

		$this->getResponse()->setResponseData($profiles);
	}
	
	/**
	 * Google Profile Id save action
	 */
	public function saveAction()
	{
		$this->isPostRequest();
		$request = $this->getRequest();
		/* @var $request \Supra\Request\HttpRequest */

		$profileId = $request->getPostValue('profile_id');
		
		$profilesList = $this->loadUserProfiles();
		
		$profileData = null;
		foreach($profilesList as $profile) {
			if ($profile['id'] == $profileId) {
				$profileData = $profile;
			}
		}
		
		if ( ! $profileData) {
			throw new \Supra\Cms\Exception\CmsException(null, "Profile with id {$profileId} not found in your profiles");
		}
		
		$writeableIni  = ObjectRepository::getIniConfigurationLoader('#google');
		if ( ! $writeableIni instanceof \Supra\Configuration\Loader\WriteableIniConfigurationLoader) {
			throw new \RuntimeException('Statistics save action requires Writeable ini loader instance');
		}
			
		$writeableIni->setValue('google_analytics', 'profile_id', $profileData['id']);
		$writeableIni->setValue('google_analytics', 'profile_title', $profileData['title']);
		$writeableIni->setValue('google_analytics', 'web_property_id', $profileData['web_property_id']);
		
		$writeableIni->write();

		$this->getResponse()
				->setStatus(true);		
	}
	
	public function deleteAction()
	{
		$provider = $this->getGoogleAnalyticsProvider();
		if ( ! $provider instanceof GoogleAnalyticsDataProvider) {
			return;
		}
		
		if ($provider->isAuthenticated()) {
			$provider->unauthorize();
		}
		
		$ini = ObjectRepository::getIniConfigurationLoader('#google');
		
		$ini->setValue('google_analytics', 'profile_id', null);
		$ini->setValue('google_analytics', 'profile_title', null);
		
		$ini->write();
	}
	
	/**
	 * @return array
	 */
	protected function loadUserProfiles()
	{
		$provider = $this->getGoogleAnalyticsProvider();
		$refreshToken = $provider->getAuthAdapter()->getRefreshToken();
		
		$profiles = false;

		$cache = ObjectRepository::getCacheAdapter($this);
		
		if ( ! empty($refreshToken)) {
			$cacheKey = get_class($this) . $refreshToken;
			$profiles = $cache->fetch($cacheKey);
		}
		
		if ($profiles === false) {
			
			$profiles = array();

			$isAuthenticated = $provider->isAuthenticated();

			if ($isAuthenticated) {
				$profilesList =  $provider->listProfiles();

				foreach($profilesList as $profile) {
					$profiles[] = array(
						'id' => $profile['profileId'],
						'web_property_id' => $profile['webPropertyId'],
						'title' => $profile['profileName'],
					);
				}
			}
			
			$cache->save($cacheKey, $profiles, 600);
		}
		
		return $profiles;
	}
	
	/**
	 * @return array
	 */
	protected function loadStatsCollection()
	{
		$provider = $this->getGoogleAnalyticsProvider();

		$visitorsData = array();
		
		$periodEnd = strtotime('Today') - 1;
		$periodStart = ($periodEnd - 60*60*24*30); // 30 days ago
		
		$provider->setPeriodStart($periodStart);
		$provider->setPeriodEnd($periodEnd);
		
		$visitorRecords = $provider->getVisitorsByDay();
		if ( ! empty($visitorRecords['entries']) && ! empty($visitorRecords['aggregates'])) {
			
			$visitorsData = array(
				'monthly' => array(
					'pageviews' => $visitorRecords['aggregates']['pageviews'],
					'visits' => $visitorRecords['aggregates']['visits'],
					'visitors' => $visitorRecords['aggregates']['visitors'],
				),
				'daily' => array(),
			);
			
			foreach($visitorRecords['entries'] as $entry) {
				/* @var $entry \Supra\Statistics\GoogleAnalytics\GoogleAnalyticsReportItem */
				$dimensions = $entry->getDimensions();
				$dayNumber = $dimensions['day'];
				
				$day = date('Y/m/d', $periodStart + (($dayNumber - 1) * 60 * 60 * 24));
				
				$visitorsData['daily'][] = array(
					'date' => $day,
					'pageviews' => $entry->getPageviews(),
					'visits' => $entry->getVisits(),
					'visitors' => $entry->getVisitors(),
				);
			}
		}
		
		// period start is 60 days ago from today, to include current period (30 days)
		// plus older 30 days to compare with
		$extendedPeriodStart = ($periodEnd - 60*60*24*60);
		$provider->setPeriodStart($extendedPeriodStart);
		
		$keywordVisitsData = array();
		
		$keywordsByDay = $provider->getTopKeywordsByDay();
		
		if ( ! empty($keywordsByDay['entries'])) {

			foreach($keywordsByDay['entries'] as $entry) {
				
				$dimensions = $entry->getDimensions();
				
				$day = $dimensions['day'];
				$month = $dimensions['month'];
				$year = $dimensions['year'];
				
				$keyword = $dimensions['keyword'];
				$visits = $entry->getVisits();

				$date = strtotime("{$day}-{$month}-{$year}");
				
				if ( ! isset($keywordVisitsData[$keyword])) {
					$keywordVisitsData[$keyword] = array(
						'current' => 0,
						'previous' => 0,
					);
				}
				
				if ($date > $periodStart) {
					$keywordVisitsData[$keyword]['current'] = $keywordVisitsData[$keyword]['current'] + $visits;
				} else {
					$keywordVisitsData[$keyword]['previous'] = $keywordVisitsData[$keyword]['previous'] + $visits;
				}
			}
		}
		
		$keywordsData = array();
		if ( ! empty($keywordVisitsData)) {
			foreach ($keywordVisitsData as $keyword => $visitsData) {
				
				if ($visitsData['current'] > 0) {
					
					$change = ($visitsData['previous'] - $visitsData['current']) * (-1);
					
					$keywordsData[] = array(
						'title' => $keyword,
						'amount' => $visitsData['current'],
						'change' => ($change == 0 ? null : $change),
					);
				}
			}
		}
		
		if ( ! empty($keywordsData)) {
			// @FIXME: not nice
			usort($keywordsData, array($this, 'feedSortCmp'));
			$keywordsData = array_slice($keywordsData, 0, 7);
		}
		
		$sourceVisitsData = array();
		
		$sourcesByDay = $provider->getTopSourcesByDay();
		
		if ( ! empty($sourcesByDay['entries'])) {

			foreach($sourcesByDay['entries'] as $entry) {
				
				$dimensions = $entry->getDimensions();
				
				$day = $dimensions['day'];
				$month = $dimensions['month'];
				$year = $dimensions['year'];
				
				$source = $dimensions['source'];
				$visits = $entry->getVisits();

				$date = strtotime("{$day}-{$month}-{$year}");
				
				if ( ! isset($sourceVisitsData[$source])) {
					$sourceVisitsData[$source] = array(
						'current' => 0,
						'previous' => 0,
					);
				}
				
				if ($date > $periodStart) {
					$sourceVisitsData[$source]['current'] = $sourceVisitsData[$source]['current'] + $visits;
				} else {
					$sourceVisitsData[$source]['previous'] = $sourceVisitsData[$source]['previous'] + $visits;
				}
			}
		}
		
		$sourcesData = array();
		if ( ! empty($sourceVisitsData)) {
			foreach ($sourceVisitsData as $source => $visitsData) {
				
				if ($visitsData['current'] > 0) {
					
					$change = ($visitsData['previous'] - $visitsData['current']) * (-1);
					
					$sourcesData[] = array(
						'title' => $source,
						'amount' => $visitsData['current'],
						'change' => ($change == 0 ? null : $change),
					);
				}
			}
		}

		if ( ! empty($sourcesData)) {
			// @FIXME: not nice
			usort($sourcesData, array($this, 'feedSortCmp'));
			$sourcesData = array_slice($sourcesData, 0, 7);
		}
		
		return array(
			'keywords' => array_values($keywordsData),
			'sources' => array_values($sourcesData),
			'visitors' => $visitorsData,
		);
	}
	
	/**
	 * @return Supra\Statistics\GoogleAnalytics\GoogleAnalyticsDataProvider
	 */
	protected function getGoogleAnalyticsProvider()
	{
		return ObjectRepository::getObject($this, 'Supra\Statistics\GoogleAnalytics\GoogleAnalyticsDataProvider');
	}
	
	private function feedSortCmp($a, $b)
	{
		if ($a['amount'] == $b['amount']) {
			return 0;
		}
		
		 return ($a['amount'] < $b['amount']) ? 1 : -1;
	}
}