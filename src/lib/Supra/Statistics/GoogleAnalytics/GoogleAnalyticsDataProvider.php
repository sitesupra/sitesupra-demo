<?php

namespace Supra\Statistics\GoogleAnalytics;

use Supra\ObjectRepository\ObjectRepository;
use Supra\Cms\Exception\CmsException;
use Supra\Statistics\Exception\RuntimeException;

class GoogleAnalyticsDataProvider {
	
	const PERIOD_DATE_FORMAT = 'Y-m-d';
	
	const GA_PREFIX = 'ga:';

	const URL_CLIENT_LOGIN = 'https://www.google.com/accounts/ClientLogin';
	const URL_DATA_FEED = 'https://www.google.com/analytics/feeds/data';
	
	const DEFAULT_TIMEOUT = 10; // seconds
	
	/**
	 * Contains authorization token, returned by ClientLogin section
	 * @var string
	 */
	protected $authToken;
	
	/**
	 * Info about Supra, taken from Info class
	 * used to ident this application when making requests to Google Analytics
	 * @var string
	 */
	protected $supraVersion;
	
	/**
	 * Some default headers, that could be usefull on each request
	 * @var array
	 */
	protected $defaultHeaders = array(
		'GData-Version: 2.0', // which version of Google Api data we are requesting
	);
	
	/**
	 * @var int
	 */
	protected $httpRequestTimeout;
	
	/**
	 * @var string
	 */
	private $accountPasswd;
	
	/**
	 * @var string
	 */
	private $accountEmail;
	
	/**
	 * @var string
	 */
	private $profileId;
	
	
	public function __construct()
	{
		$systemInfo = ObjectRepository::getSystemInfo($this);
		$this->supraVersion = $systemInfo->name . $systemInfo->version;
		
		$this->httpRequestTimeout = self::DEFAULT_TIMEOUT;
	}

	/**
	 * @param string $id
	 */
	public function setAccountEmail($email)
	{
		$this->accountEmail = $email;
	}
	
	/**
	 * @param string $passwd
	 */
	public function setAccountPasswd($passwd)
	{
		$this->accountPasswd = $passwd;
	}
	
	/**
	 * @param string $id
	 */
	public function setProfileId($id)
	{
		$this->profileId = $id;
	}
	
	/**
	 * Overrides default http request timeout
	 * 
	 * @param int $seconds
	 */
	public function setRequestTimeout($seconds)
	{
		$this->httpRequestTimeout = $seconds;
	}
		
	/**
	 * Access to Google API data requires user to be authenticated
	 * This will tries to do that (using ClientLogin auth) and, 
	 * if suceeded, stores received auth token
	 * inside property
	 * 
	 * @param string $email
	 * @param string $passwd
	 */
	public function authenticate($email, $passwd)
	{
		if (empty($email) || empty($passwd)) {
			throw new RuntimeException('Missing authentification configuration for Google Services');
		}
		
		$requestVars = array(
			'accountType' => 'GOOGLE',
			'Email' => $email,
			'Passwd' => $passwd,
			'service' => 'analytics',
			'source' => $this->supraVersion,
		);
		
		$response = $this->request(self::URL_CLIENT_LOGIN, $requestVars, array(), 'POST');
		
		$matches = null;
		preg_match("/Auth=([a-z0-9_\-]+)/i", $response, $matches);

		if (empty($matches)) {
			//throw new RuntimeException('Failed to authenticate at Google Services');
		}
		
		$this->authToken = $matches[1];
	}
	
	/**
	 * Wrapper for request method
	 * Currently wraps fopenRequest() method,
	 * if CURL extension is present, 
	 * it is possible to use it also using curlRequest
	 * 
	 * @param type $requestUrl
	 * @param array $requestVars
	 * @param array $headers
	 * @param 
	 * @return string
	 */
	protected function request($requestUrl, array $requestVars = array(), array $headers = array(), $requestMethod = 'GET')
	{
		return $this->fopenRequest($requestUrl, $requestVars, $headers, $requestMethod);
	}
	
	/**
	 * Does a HTTP request using CURL library, to $requestUrl, with $requestVars and, 
	 * if present in $headers, additional request headers. 
	 * Request method (GET/POST) could be specified in $requestMethod
	 * 
	 * @param string $requestUrl
	 * @param array $requestVars
	 * @param array $headers
	 * @param string $requestMethod
	 * @return string
	 */
//	protected function curlRequest($requestUrl, array $requestVars = array(), array $headers = array(), $requestMethod = 'GET')
//	{
//		// if request is a GET request, apply request vars to URL string
//		if ($requestMethod == 'GET' && ! empty($requestVars)) {
//			$requestUrl = $requestUrl . '?' . http_build_query($requestVars);
//		}
//		
//		$curl = curl_init($requestUrl);
//		
//		// default headers
//		$headers = array_merge($headers, $this->defaultHeaders);
//		
//		// authorization token header, if set already
//		if ( ! empty($this->authToken)) {
//			array_push($headers, 'Authorization: GoogleLogin auth=' . $this->authToken);
//		}
//		
//		// apply headers
//		if ( ! empty($headers)) {
//			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
//		}
//		 
//		if ($requestMethod == 'POST' && ! empty($requestVars)) {
//			curl_setopt($curl, CURLOPT_POSTFIELDS, $requestVars);
//		}
//		
//		curl_setopt($curl, CURLOPT_POST, ($requestMethod == 'POST' ? true : false));
//
//		$response = curl_exec($curl);
//		
//		curl_close($curl);
//		
//		return $response;
//	}
	
	/**
	 * Does a HTTP request using URL fopen, to $requestUrl, with $requestVars and, 
	 * if present in $headers, additional request headers. 
	 * Request method (GET/POST) could be specified in $requestMethod
	 * 
	 * @param string $requestUrl
	 * @param array $requestVars
	 * @param array $headers
	 * @param string $requestMethod
	 * @return string
	 */
	protected function fopenRequest($requestUrl, array $requestVars = array(), array $headers = array(), $requestMethod = 'GET')
	{
		$contextOptions = array(
			'http' => array(
				'method' => $requestMethod,
				'timeout' => $this->httpRequestTimeout,
				'ignore_errors' => true,
			),
		);
		
		$httpOptions = &$contextOptions['http'];
		
		$httpRequestVars = null;
		if ( ! empty($requestVars)) {
			$httpRequestVars = http_build_query($requestVars);
		}
		
		$headers = array_merge($headers, $this->defaultHeaders);
		
		if ( ! empty($this->authToken)) {
			array_push($headers, 'Authorization: GoogleLogin auth=' . $this->authToken);
		}
		
		$requestHeaders = null;
		if ( ! empty($headers)) {
			$requestHeaders = implode("\r\n", $headers) . "\r\n";
		}
		
		if ($requestMethod == 'POST') {
			$requestHeaders = "Content-type: application/x-www-form-urlencoded\r\nContent-Length: " . strlen($httpRequestVars) . "\r\n" . $headers;
			
			$httpOptions['content'] = $httpRequestVars;
		} else {
			$requestUrl = $requestUrl . '?' . $httpRequestVars;
		}
    
		if ( ! is_null($requestHeaders)) {
			$httpOptions['header'] = $headers;
		}
		
		$context = stream_context_create($contextOptions);
		
		$response = file_get_contents($requestUrl, null, $context);
		if (isset($http_response_header) && ! empty($http_response_header)) {
			$this->checkResponseHeaders($http_response_header);
		}

		return $response;
	}
	
	protected function checkResponseHeaders($headerArray)
	{
		$matches = array();
		preg_match('#HTTP/\d+\.\d+ (\d+)#', $headerArray[0], $matches);
		
		if (empty($matches) ||  ! isset($matches[1])) {
			throw new RuntimeException('Wrong response headers');
		}
		
		// TODO: could check also a string notation of status code, to return more clear error reason
		switch($matches[1]) {
			case 200:
				// all went ok
				break;
			case 400:
				throw new RuntimeException('Wrong query');
				break;
			case 401:
				// Invalid credentials
				throw new RuntimeException('Authorization token is invalid or expired');
				break;
			case 403:
				// this could be exceeded quota, non authorized user, or insufficient permissions
				throw new RuntimeException('User has no access to Google Services');
				break;
			case 503:
				throw new RuntimeException('Google Services server returned an error');
				break;
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
			// TODO: perform request to Management API and fetch profile id 
			throw new RuntimeException('ProfileID should be defined to perform Data Feed requests');
		}
		
		$requestVars = array(
			'ids' => 'ga:' . $this->profileId,
			'metrics' => implode(',', $metrics),
			'start-date' => $fromDate,
			'end-date' => $tillDate,
		);
		
		// limits max return results
		// Google defaults is 1000
		// Max. value = 10 000
		if ( ! empty($limit)) {
			$requestVars['max-results'] = (int) $limit;
		}
		
		if ( ! empty($dimensions)) {
			$requestVars['dimensions'] = implode(',', $dimensions);
		}
		
		if ( ! empty($sort)) {
			$requestVars['sort'] = implode(',', $sort);
		}
		
		// TODO: implement filters, segment and start_index
		
		$rawResponse = $this->request(self::URL_DATA_FEED, $requestVars);
		
		$response = $this->parseRawResponse($rawResponse);
		
		return $response;
	}
	
	/**
	 * Parses raw report data from Google Analytics and returns an array with
	 * available aggregated metric values and report entries represented by 
	 * GoogleAnalyticsReportItem objects
	 * 
	 * @param string $rawResponse
	 * @return array
	 */
	protected function parseRawResponse($rawResponse) 
	{
		$response = array();
		
		$xmlObject = simplexml_load_string($rawResponse);
		
		$results = $xmlObject->children('http://schemas.google.com/analytics/2009');
		
		// aggregated metric values
		foreach ($results->aggregates->metric as $aggregateMetric) {
			
			$name = str_replace(self::GA_PREFIX, '', strval($aggregateMetric->attributes()->name));
			$value = $this->parseIntValue(strval($aggregateMetric->attributes()->value));
      
			$response['aggregates'][$name] = $value;

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
			
			$response['entries'][] = new GoogleAnalyticsReportItem($metrics, $dimensions);
			
		}
		
		return $response;
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
		$sort = array('ga:visits');
		
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
		$sort = array('ga:visits');
		
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
}