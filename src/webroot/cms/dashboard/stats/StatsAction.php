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
		
	
	public function statsAction()
	{
		$response = $this->getResponse();
		
		$userConfig = ObjectRepository::getIniConfigurationLoader($this);
		
		$accountEmail = $userConfig->getValue('googleAnalytics', 'email', null);
		$accountPasswd = $userConfig->getValue('googleAnalytics', 'password', null);
		$profileId = $userConfig->getValue('googleAnalytics', 'profile_id', null);
		
		if (is_null($accountEmail) || is_null($accountPasswd) || is_null($profileId)) {
			$response->setResponseData(array(
				'keywords' => array(),
				'sources' => array(),
			));	
			return;
		}
		
		$responseArray = array();
		
		$refreshInterval = $userConfig->getValue('googleAnalytics', 'refresh_interval', '5 hours');
		
		// TODO: store data partially when it will be known, 
		// how top sources/keywords will be shown
		$cache = ObjectRepository::getCacheAdapter($this);
		
		$responseArray = $cache->fetch(__class__);
		if ($responseArray === false) {
			
			$dataProvider = new GoogleAnalyticsDataProvider();
			$dataProvider->authenticate($accountEmail, $accountPasswd);
			$dataProvider->setProfileId($profileId);

			$now = time();
			$interval = self::WEEK_PERIOD;

			//$startTime  = (self::STATS_INCLUDE_TODAY ? $now : $now - self::DAY_PERIOD);
			$startTime  = (self::STATS_INCLUDE_TODAY === true ? $now : $now - $interval);
			
			for ($i = 0; $i < self::STATS_PERIODS; $i++) {

				$startDay = date('Y-m-d', $startTime);
				$endDay = date('Y-m-d', $startTime + $interval);

				$period = array(
					$startDay,
					$endDay,
				);

				$return = &$responseArray[$startDay];

				//$return['pageviews'] = $dataProvider->getPageViewCount($period);
				//$return['visitors'] = $dataProvider->getVisitorCount($period);
				//$return['visits'] = $dataProvider->getVisitCount($period);
				
				$return = $dataProvider->getDasboardCommonStats($period);
				//$return = array();
				
				$return['keywords'] = $dataProvider->getTopKeywords($period, 10);
				$return['sources'] = $dataProvider->getTopSources($period, 10);
				
				$startTime = $startTime - $interval;
			}
			
			$responseArray = $this->prepareStatsOutput($responseArray);
			$cache->save(__class__, $responseArray, strtotime($refreshInterval));
			
			// debug info
			$responseArray['cacheVersion'] = false;
		} else {
			$responseArray['cacheVersion'] = true;
		}
		
		$response->setResponseData($responseArray);
		
	}
	
	/**
	 * Helper method to prepare stats output as it expects JS
	 * temporary
	 */
	protected function prepareStatsOutput($statsArray)
	{
		$statsArray = array_reverse($statsArray, true);
		
		$keywords = $sources = array();
		foreach ($statsArray as $key => &$dailyStats) {
			
			foreach($dailyStats['keywords'] as &$keywordStats) {
				
				if (isset($keywords[$keywordStats['title']])) {
					
					$amount = $keywordStats['amount'] - $keywords[$keywordStats['title']];
					$keywordStats['change'] = ($amount !== 0 ? $amount : null);
						
				}
				$keywords[$keywordStats['title']] = $keywordStats['amount'];
			}
			
			foreach($dailyStats['sources'] as &$sourceStats) {
				
				if (isset($sources[$sourceStats['title']])) {
					$amount = $sourceStats['amount'] - $sources[$sourceStats['title']];
					$sourceStats['change'] = ($amount !== 0 ? $amount : null);
				}
				$sources[$sourceStats['title']] = $sourceStats['amount'];
			}
		}
		
		if (is_array($statsArray)) {
			return array_pop($statsArray);
		} 
		
		return array('keywords' => array(), 'sources' => array());
	}

}