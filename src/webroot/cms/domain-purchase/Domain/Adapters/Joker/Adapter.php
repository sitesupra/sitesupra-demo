<?php

namespace Supra\Cms\DomainPurchase\Domain\Adapters\Joker;

use Supra\Cms\DomainPurchase\Domain\Domain;
use Supra\Cms\DomainPurchase\Domain\ResourceRecord;

class Adapter extends AdapterAbstraction
{

	const REQUEST_URI = 'https://dmapi.joker.com/request/';
	const REQUEST_TIMEOUT = 10; // http request timeout in seconds
	
	const REPLY_BODY_DELIMITER = '---reply---';
	const REPLY_VALUE_DELIMITER = ':';
	const LIST_OFFSET = '_LIST';
	
	const Q_DOMAIN_LIST = 'query-domain-list';
	const Q_DOMAIN_REGISTER = 'domain-register';
	const Q_DOMAIN_RENEW = 'domain-renew';
	const Q_DOMAIN_MODIFY = 'domain-modify';
	const Q_DOMAIN_DELETE = 'domain-delete';
	const Q_DOMAIN_OWNER_CHANGE = 'domain-owner-change';
	const Q_DOMAIN_LOCK = 'domain-lock';
	const Q_DOMAIN_UNLOCK = 'domain-unlock';
	const Q_DNS_ZONE_GET = 'dns-zone-get';
	
	const Q_LOGIN = 'login';
	
	/**
	 * Contains authorization token returned by authenticate() method
	 * @var string
	 */
	protected $authToken;
	
	
	public function __construct()
	{
		$this->httpRequestTimeout = self::REQUEST_TIMEOUT;
	}
	
	
	public function checkDomainAvailability(Domain $domain)
	{
		
	}
	
	public function purchaseDomain(Domain $domain)
	{
		
	}
	
	public function listAllDomainRecords(Domain $domain)
	{
		
	}
	
	public function getDomainRecord(Domain $domain, $type)
	{
		$fqdn = $domain->getFullyQualifiedDomainName();
		
		$params = array(
			'domain' => $fqdn,
		);
		
		$responseData = $this->request(self::Q_DNS_ZONE_GET, $params);
		
		foreach($responseData as $recordData) {
			$record = ResourceRecord\NullRecord::factory($recordData['type']);
			$domain->addRecord($record);
		}
	}
	
	public function modifyDomainRecord(Domain $domain, $type, $params)
	{
		
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
	 * List of registered domains
	 */
	public function listDomains()
	{
		//$this->request('')
	}
	
	
	/**
	 * Authenticate user at DMAPI service
	 * 
	 * @param string $email
	 * @param string $passwd
	 */
	protected function authenticate($username, $passwd)
	{
		$params = array(
			'username' => $username,
			'password' => $passwd,
		);
		
		$responseData = $this->request(self::Q_LOGIN, $params);
		
		if (empty($responseData['Auth-SID'])) {
			throw new AuthenticationFailure('Failed to authentication');
		}
		
		$this->authToken = $responseData['Auth-SID'];
		
		// TODO: list of available TLD's is inside 
		// $responseData[self::LIST_OFFSET] (at least, it should be there)
		// should we use it somehow (check, validation?)
	}
	
	/**
	 * Make a request to Joker DMAPI service
	 * 
	 * @param string $query
	 * @param array $parameters
	 */
	protected function request($query, array $parameters) 
	{
		$requestUri = self::REQUEST_URI . '?' . $query;
		
		if ( ! is_null($this->authToken)) {
			$parameters['auth-sid'] = $this->authToken;
		}
		
		$rawResponse = $this->fopenRequest($requestUri, $parameters);
		
		// handle response errors?
		
		$responseData = $this->parseResponse($rawResponse);
		
		return $responseData; 
	}
	
	/**
	 * Parses the raw response returned by request
	 * into array of keys/values
	 * 
	 * If value contains no key, it will be placed in additional $list array
	 * 
	 * @param string $rawResponse
	 * @return array
	 */
	protected function parseResponse($rawResponse)
	{
		$responseData = array();
		$list = array();
		
		$replyStartPos = strpos($rawResponse, self::REPLY_BODY_DELIMITER) + count(self::REPLY_BODY_DELIMITER);
		$replyEndPos = strpos($rawResponse, self::REPLY_BODY_DELIMITER, $replyStartPos);

		$replyBody = substr($rawResponse, $replyStartPos, ($replyEndPos - $replyStartPos));
		
		$replyLines = explode("\n", $replyBody);
		foreach($replyLines as $line) {
			
			if (empty($line)) {
				continue;
			}
			
			$name = $value = null;
			list($name, $value) = explode(self::REPLY_VALUE_DELIMITER, $line);
			
			if ( ! empty($name) && ! empty($value)) {
				$responseData[$name] = $value;
			} else {
				$list[] = $line;	
			}
		}
		
		if ( ! empty($list)) {
			$responseData[self::LIST_OFFSET] = $list;
		}
		
		return $responseData;
	}
	
	/**
	 *
	 * @param string $requestUri
	 * @param array $requestVars
	 * @return string
	 */
	protected function fopenRequest($requestUri, array $requestVars = array())
	{
		$contextOptions = array(
			'http' => array(
				'method' => 'POST',
				'timeout' => $this->httpRequestTimeout,
				'ignore_errors' => true,
			),
		);
		
		$httpOptions = &$contextOptions['http'];
		
		$httpRequestVars = null;
		if ( ! empty($requestVars)) {
			$httpRequestVars = http_build_query($requestVars);
		}
		
		$requestHeaders = "Content-type: application/x-www-form-urlencoded\r\nContent-Length: " . strlen($httpRequestVars) . "\r\n";
		$httpOptions['content'] = $httpRequestVars;

		if ( ! is_null($requestHeaders)) {
			$httpOptions['header'] = $headers;
		}
		
		$context = stream_context_create($contextOptions);
		
		$response = file_get_contents($requestUrl, null, $context);
		if (isset($http_response_header) && ! empty($http_response_header)) {
			//$this->checkResponseHeaders($http_response_header);
		}

		return $response;
	}
}
