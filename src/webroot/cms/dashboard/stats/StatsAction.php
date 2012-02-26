<?php

namespace Supra\Cms\Dashboard\Stats;

use Supra\Cms\CmsAction;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Statistics\GoogleAnalytics\GoogleAnalyticsDataProvider;

class StatsAction extends CmsAction
{
	
	const STATS_PERIOD_DAYS = 7;
	const STATS_INCLUDE_TODAY = false;
	
	const DAY_PERIOD = 86400;
		
	public function listAction()
	{
		$response = $this->getResponse();
		
		$userConfig = ObjectRepository::getIniConfigurationLoader($this);
		
		$accountEmail = $userConfig->getValue('googleAnalytics', 'email', null);
		$accountPasswd = $userConfig->getValue('googleAnalytics', 'password', null);
		$profileId = $userConfig->getValue('googleAnalytics', 'profile_id', null);
		
		if (is_null($accountEmail) || is_null($accountPasswd) || is_null($profileId)) {
			$response->setResponseData(false);	
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

			$startTime  = (self::STATS_INCLUDE_TODAY ? $now : $now - self::DAY_PERIOD);

			for ($i = 0; $i < self::STATS_PERIOD_DAYS; $i++) {

				$startDay = date('Y-m-d', $startTime);
				$nextDay = date('Y-m-d', $startTime + self::DAY_PERIOD);

				$period = array(
					$startDay,
					$nextDay,
				);

				$return = &$responseArray[$startDay];

				//$return['pageviews'] = $dataProvider->getPageViewCount($period);
				//$return['visitors'] = $dataProvider->getVisitorCount($period);
				//$return['visits'] = $dataProvider->getVisitCount($period);
				
				$return = $dataProvider->getDasboardCommonStats($period);
				
				$return['keywords'] = $dataProvider->getTopKeywords($period, 10);
				$return['sources'] = $dataProvider->getTopSources($period, 10);
			
				$startTime = $startTime - self::DAY_PERIOD;
			}
			
			$cache->save(__class__, $responseArray, strtotime($refreshInterval));
		}
		
	}

}