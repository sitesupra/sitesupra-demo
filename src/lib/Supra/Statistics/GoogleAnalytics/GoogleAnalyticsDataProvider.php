<?php

namespace Supra\Statistics\GoogleAnalytics;

use Supra\Statistics\Exception\RuntimeException;
use Supra\RemoteHttp\Request\RemoteHttpRequest;
use Supra\RemoteHttp\Response\RemoteHttpResponse;

/**
 * 
 */
class GoogleAnalyticsDataProvider 
{
	/**
	 * SiteSupra oAuth2 web-application configuration
	 * @TODO: move values to ...? 
	 */
	const SUPRA_CLIENT_SECRET = '4fkvgHQIqsXjrYt_UjN_U8kS';
	const SUPRA_CLIENT_ID = '833104259663.apps.googleusercontent.com';
	const SUPRA_REDIRECT_URI = 'http://sitesupra.net/oauth2callback';
	
	const URL_FEED_DATA = 'https://www.googleapis.com/analytics/v2.4/data';
	const URL_BASE_FEED_MANAGEMENT = 'https://www.googleapis.com/analytics/v2.4/management/';
	
	const PERIOD_DATE_FORMAT = 'Y-m-d';
	const GA_PREFIX = 'ga:';

	/**
	 * @var array
	 */
	protected $defaultHeaders = array();

	/**
	 * @var string
	 */
	protected $profileId;
	
	/**
	 * @var \Supra\RemoteHttp\RemoteHttpRequestService
	 */
	protected $requestService;
	
	/**
	 * @var Authentication\OAuth2Authentication
	 */
	protected $authAdapter;
	
	/**
	 * 
	 */
	public function __construct()
	{
		$authAdapter = new Authentication\OAuth2Authentication();
		
		$authAdapter->setClientId(self::SUPRA_CLIENT_ID);
		$authAdapter->setClientSecret(self::SUPRA_CLIENT_SECRET);
		$authAdapter->setRedirectUri(self::SUPRA_REDIRECT_URI);
		
		$authAdapter->setStorage(new Authentication\Storage\WriteableIniStorage);

		$this->authAdapter = $authAdapter;
		
		$this->requestService = new \Supra\RemoteHttp\RemoteHttpRequestService();
	}
	
	/**
	 * @param string $profileId
	 */
	public function setProfileId($profileId)
	{
		$this->profileId = $profileId;
	}

	/**
	 * @return Authentication\OAuth2Authentication
	 */
	public function getAuthAdapter()
	{
		return $this->authAdapter;
	}
	
	/**
	 * @return boolean
	 */
	public function isAuthenticated()
	{
		return $this->authAdapter->isAuthenticated();
	}
	
	/**
	 * @return RemoteHttpResponse
	 */
	protected function doRequest($requestUrl, $method = 'GET', $requestVars = array(), $headers = array())
	{
		if ( ! $this->authAdapter->isAuthenticated()) {
			throw new RuntimeException('You should authenticate before you request Google Analytics feed data');
		}
		
		$request = new RemoteHttpRequest($requestUrl, $method, $requestVars);
		foreach ($headers as $name => $value) {
			$request->header($name, $value);
		}
		
		$this->authAdapter->sign($request);
		
		$response = $this->requestService->makeRequest($request);
		
		$this->validateResponse($response);
	
		return $response;
	}
	
	/**
	 * 
	 */
	protected function validateResponse(RemoteHttpResponse $response)
	{
		$responseCode = $response->getCode();
			
		switch($responseCode) {
			case 200:
				break;
			case 400:
				throw new RuntimeException('Wrong request query');
				break;
			case 401:
				throw new RuntimeException('Authorization token is invalid or expired');
				break;
			case 403:
				// this could be exceeded quota, non authorized user, or insufficient permissions
				throw new RuntimeException('User has no access to Google Services');
				break;
			case 503:
				throw new RuntimeException('Google Services server returned an error');
				break;
			default:
				throw new RuntimeException("Unknown response code {$responseCode} received");
		}
	}
	
	/**
	 * 
	 * @param array $metrics
	 * @param string $fromDate
	 * @param string $tillDate
	 * @param array $dimensions
	 * @param array $sort
	 * @param int $limit
	 * @return array
	 */
	protected function requestDataFeed(array $metrics, $fromDate, $tillDate, array $dimensions = array(), array $sort = array(), $limit = null)
	{	
		if (empty($this->profileId)) {
			throw new RuntimeException('Profile ID should be defined to perform Google Analytics Data Feed requests');
		}
		
		$fromDate = date(self::PERIOD_DATE_FORMAT, $fromDate);
		$tillDate = date(self::PERIOD_DATE_FORMAT, $tillDate);
		
		$requestVars = array(
			'ids' => 'ga:' . $this->profileId,
			'metrics' => implode(',', $metrics),
			'start-date' => $fromDate,
			'end-date' => $tillDate,
		);
		
		// limits max return results
		// Google defaults is 1000
		// Max. value = 10000
		if ( ! empty($limit)) {
			$requestVars['max-results'] = (int) $limit;
		}
		
		if ( ! empty($dimensions)) {
			$requestVars['dimensions'] = implode(',', $dimensions);
		}
		
		if ( ! empty($sort)) {
			$requestVars['sort'] = implode(',', $sort);
		}
		
		//@TODO: implement filters, segment and start_index
		
		$response = $this->doRequest(self::URL_FEED_DATA, 'GET', $requestVars);
		
		$body = $response->getBody();
		$feedData = $this->parseFeedResponse($body);
		
		return $feedData;
	}
	
	protected function parseIntValue($value) {
		
		if (preg_match('/^(\d+\.\d+)|(\d+E\d+)|(\d+.\d+E\d+)$/', $value)) {
			$value = floatval($value);
		} else {
			$value = intval($value);
		}
		
		return $value;
	}
	
	public function getPageViewCount(array $period) 
	{
		$metrics = array('ga:pageviews');
		
		list($from, $till) = $period;
			
		$response = $this->requestDataFeed($metrics, $from, $till);

		if (isset($response['aggregates']['pageviews'])) {
			return $response['aggregates']['pageviews'];
		}
		
		return null;
	}
	
	public function getVisitorCount(array $period)
	{
		$metrics = array('ga:visitors');
		
		list($from, $till) = $period;
			
		$response = $this->requestDataFeed($metrics, $from, $till);

		if (isset($response['aggregates']['visitors'])) {
			return $response['aggregates']['visitors'];
		}
		
		return null;
	}
	
	public function getVisitCount(array $period)
	{
		$metrics = array('ga:visits');
		
		list($from, $till) = $period;
			
		$response = $this->requestDataFeed($metrics, $from, $till);

		if (isset($response['aggregates']['visits'])) {
			return $response['aggregates']['visits'];
		}
		
		return null;
	}
	
	public function getTopSources(array $period, $limit = 50)
	{
		/*
		return array(
			array('title' => 'www.google.lv', 'amount' => rand(55, 300)),
			array('title' => 'www.ss.lv', 'amount' => rand(10, 50)),
		);
		 */
		
		$metrics = array('ga:visits');
		$dimensions = array('ga:source');
		$sort = array('-ga:visits');
		
		list($from, $till) = $period;
			
		$response = $this->requestDataFeed($metrics, $from, $till, $dimensions, $sort, $limit);

		$list = array();
		
		if (isset($response['entries'])) {
			foreach ($response['entries'] as $entry) {
				$source = $entry->getSource();
				$visitCount = $entry->getVisits();
				
				$list[] = array(
					'title' => $source,
					'amount' => $visitCount,
				);
				
			}
		}
		
		return $list;
	}
	
	public function getTopKeywords(array $period, $limit = 50)
	{
		/*
		return array(
			array('title' => 'supra7', 'amount' => rand(55, 300)),
			array('title' => 'cms', 'amount' => rand(10, 50)),
		);
		 */
		
		$metrics = array('ga:visits');
		$dimensions = array('ga:keyword');
		$sort = array('-ga:visits');
		
		list($from, $till) = $period;
			
		$response = $this->requestDataFeed($metrics, $from, $till, $dimensions, $sort, $limit);

		$list = array();
		
		if (isset($response['entries'])) {
			foreach ($response['entries'] as $entry) {
				$keyword = $entry->getKeyword();
				$visitCount = $entry->getVisits();
				
				$list[] = array(
					'title' => $keyword,
					'amount' => $visitCount,
				);
				
			}
		}
		
		return $list;
	}
	
	/**
	 * Returns an array with visitor/pageview/visit stats loaded in one query
	 * 
	 * @param array $period
	 * @return array
	 */
	public function getDasboardCommonStats(array $period)
	{
		$result = array(
			'visits' => null,
			'visitors' => null,
			'pageviews' => null,
		);
		
		$metrics = array('ga:visitors', 'ga:pageviews', 'ga:visits');
		
		list($from, $till) = $period;
		
		$response = $this->requestDataFeed($metrics, $from, $till);
		
		if (isset($response['aggregates']['visits'])) {
			$result['visits'] = $response['aggregates']['visits'];
		}
		
		if (isset($response['aggregates']['visitors'])) {
			$result['visitors'] = $response['aggregates']['visitors'];
		}
		
		if (isset($response['aggregates']['pageviews'])) {
			$result['pageviews'] = $response['aggregates']['pageviews'];
		}
		
		return $result;
	}
		
	/**
	 * @return array
	 */
	public function listProfiles()
	{
		$requestUrl = self::URL_BASE_FEED_MANAGEMENT . 'accounts/~all/webproperties/~all/profiles/';
		$response = $this->doRequest($requestUrl, 'GET');
		
		$body = $response->getBody();
		$profiles = $this->parseProfileResponse($body);
		
		return $profiles;
	}
	
	/**
	 * 
	 * @param string $response
	 * @return array
	 */
	private function parseProfileResponse($response)
	{
		$profiles = array();
		
		$feedObject = simplexml_load_string($response);
		
		if ( ! empty($feedObject->entry)) {
			foreach ($feedObject->entry as $entry) {
				$profileProperties = array();
				foreach ($entry->children('http://schemas.google.com/analytics/2009')->property as $property) {
					$profileProperties[str_replace('ga:','',$property->attributes()->name)] = strval($property->attributes()->value);
				}
				
				$profiles[] = $profileProperties;
			}	
		}
		
		return $profiles;
	}
	
	/**
	 * 
	 */
	private function parseFeedResponse($response)
	{
		$feedData = array();
		
		$xmlObject = simplexml_load_string($response);
		
		$results = $xmlObject->children('http://schemas.google.com/analytics/2009');
		
		// aggregated metric values
		foreach ($results->aggregates->metric as $aggregateMetric) {
			
			$name = str_replace(self::GA_PREFIX, '', strval($aggregateMetric->attributes()->name));
			$value = $this->parseIntValue(strval($aggregateMetric->attributes()->value));
      
			$feedData['aggregates'][$name] = $value;

		}
		
		// result entries
		// each entry contains metric values grouped by specified dimensions
		// for example, when metric = ga:visits and dimension = ga:keyword
		// each entry will contain amount of visits for a single keyword
		// that could be represented by simple array of "keyword" => "visit count"
		// "first keyword" => 15145
		// "second keyword" => 44778
		// "another one keyword" => 789
		//
		foreach($xmlObject->entry as $entry) {
			
			$metrics = array();
			foreach ($entry->children('http://schemas.google.com/analytics/2009')->metric as $metric) {
				
				$name = str_replace(self::GA_PREFIX, '', $metric->attributes()->name);
				$value = $this->parseIntValue(strval($metric->attributes()->value));
				
				$metrics[$name] = $value;
				
			}
			
			$dimensions = array();
			foreach ($entry->children('http://schemas.google.com/analytics/2009')->dimension as $dimension) {
			
				$name = str_replace(self::GA_PREFIX, '', $dimension->attributes()->name);
				$value = strval($dimension->attributes()->value);
					
				$dimensions[$name] = $value;
				
			}
			
			$feedData['entries'][] = new GoogleAnalyticsReportItem($metrics, $dimensions);
			
		}
		
		return $feedData;
	}
}