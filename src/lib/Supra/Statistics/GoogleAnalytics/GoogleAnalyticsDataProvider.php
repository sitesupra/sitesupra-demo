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
	const URL_FEED_DATA = 'https://www.googleapis.com/analytics/v2.4/data';
	const URL_BASE_FEED_MANAGEMENT = 'https://www.googleapis.com/analytics/v2.4/management/';
	
	const PERIOD_DATE_FORMAT = 'Y-m-d';
	const GA_PREFIX = 'ga:';

	/**
	 * @var array
	 */
	protected $defaultHeaders = array(
		'Accept-Encoding' => 'gzip',
		'User-Agent' => 'cURL/php5 (gzip)',
	);

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
	 * @return \Supra\Statistics\GoogleAnalytics\Authentication\AuthenticationInterface
	 */
	public function getAuthAdapter()
	{
		return $this->authAdapter;
	}
	
	/**
	 * @param \Supra\Statistics\GoogleAnalytics\Authentication\AuthenticationInterface $adapter
	 */
	public function setAuthAdapter(Authentication\AuthenticationInterface $adapter)
	{
		$this->authAdapter = $adapter;
	}
	
	/**
	 * @return boolean
	 */
	public function isAuthenticated()
	{
		return $this->authAdapter->isAuthenticated();
	}
	
	/**
	 * @return boolean
	 */
	public function unauthorize()
	{
		return $this->authAdapter->unauthorize();
	}
	
	/**
	 * @return RemoteHttpResponse
	 */
	protected function doRequest($requestUrl, $method = 'GET', $requestVars = array(), $headers = array())
	{
		\Log::info("Requesting GoogleAnalytics service by {$requestUrl}");
				
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

			case 401:
				$this->authAdapter->unauthorize();
				throw new Exception\UnauthorizedAccessException('Authorization token is invalid or expired');				

			case 403:
				// this could be exceeded quota, non authorized user, or insufficient permissions
				throw new RuntimeException('User has no access to Google Services');

			case 503:
				throw new RuntimeException('Google Services server returned an error');

			default:
				throw new RuntimeException("Unknown response code {$responseCode} received");
		}
	}
	
	/**
	 *
	 */
	public function requestDataFeed(array $metrics, array $dimensions = array(), array $sort = array(), array $filters = array(), $limit = null)
	{	
		if (empty($this->profileId)) {
			throw new RuntimeException('Profile ID should be defined to perform Google Analytics Data Feed requests');
		}
		
		if (empty($this->startTime) || empty($this->endTime)) {
			throw new RuntimeException('No period defined');
		}
		
		$requestVars = array(
			'ids' => 'ga:' . $this->profileId,
			'metrics' => implode(',', $metrics),
			'start-date' => $this->startTime,
			'end-date' => $this->endTime,
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
		
		if ( ! empty($filters)) {
			$requestVars['filters'] = implode(',', $filters);
		}
		
		//@TODO: implement filters, segment and start_index
		
		$response = $this->doRequest(self::URL_FEED_DATA, 'GET', $requestVars);
		
		$body = $response->getBody();
		$feedData = $this->parseFeedResponse($body);
		
		return $feedData;
	}
	
	/** 
	 * @param integer $timestamp
	 */
	public function setPeriodStart($timestamp)
	{
		$this->startTime = date(self::PERIOD_DATE_FORMAT, $timestamp);
	}
	
	/**
	 * @param integer $timestamp
	 */
	public function setPeriodEnd($timestamp)
	{
		$this->endTime = date(self::PERIOD_DATE_FORMAT, $timestamp);
	}
	
	/**
	 * @param string $value
	 * @return number
	 */
	protected function parseIntValue($value) {
		
		if (preg_match('/^(\d+\.\d+)|(\d+E\d+)|(\d+.\d+E\d+)$/', $value)) {
			$value = floatval($value);
		} else {
			$value = intval($value);
		}
		
		return $value;
	}
	
	/**
	 * @return array
	 */
	public function getVisitorsByDay()
	{
		$dimensions = array('ga:day');
		$metrics = array('ga:visits', 'ga:pageviews', 'ga:visitors');
		$sort = array('ga:day');
	
		$response = $this->requestDataFeed($metrics, $dimensions, $sort);
		
		if ( ! empty($response['entries']) && ! empty($response['aggregates'])) {
			return $response;
		}
		
		return array();
	}
	
	public function getTopSourcesByDay()
	{
		$dimensions = array('ga:source', 'ga:month', 'ga:year', 'ga:day');
		$metrics = array('ga:visits');
		$sort = array('-ga:year', '-ga:month', '-ga:day', '-ga:visits');
		$filters = array('ga:source!=(direct)');
	
		$response = $this->requestDataFeed($metrics, $dimensions, $sort, $filters);
		
		if ( ! empty($response['entries'])) {
			return $response;
		}
		
		return array();
	}
	
	public function getTopKeywordsByDay()
	{
		$dimensions = array('ga:keyword', 'ga:month', 'ga:year', 'ga:day');
		$metrics = array('ga:visits');
		$sort = array('-ga:year', '-ga:month', '-ga:day', '-ga:visits');
		$filters = array('ga:keyword!=(not set)');
	
		$response = $this->requestDataFeed($metrics, $dimensions, $sort, $filters);
		
		if ( ! empty($response['entries'])) {
			return $response;
		}
		
		return array();
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