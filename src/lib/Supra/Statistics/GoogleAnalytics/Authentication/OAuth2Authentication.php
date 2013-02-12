<?php

namespace Supra\Statistics\GoogleAnalytics\Authentication;

use Supra\RemoteHttp\Request\RemoteHttpRequest;
use Supra\RemoteHttp\Response\RemoteHttpResponse;
use Supra\RemoteHttp\RemoteHttpRequestService;


/**
 * 
 */
class OAuth2Authentication implements AuthenticationInterface 
{
	const SCOPE_ANALYTICS = 'https://www.google.com/analytics/feeds/';
	const SCOPE_USERINFO_EMAIL = 'https://www.googleapis.com/auth/userinfo.email';
	
	const URL_OAUTH2_AUTH = 'https://accounts.google.com/o/oauth2/auth';
	const URL_OAUTH2_REVOKE = 'https://accounts.google.com/o/oauth2/revoke';
	const URL_OAUTH2_TOKEN = 'https://accounts.google.com/o/oauth2/token';
	
	protected $accessToken = null;
	protected $accessTokenExpiresIn = null;
	protected $accessTokenCreated = null;
	
	private $loaded = false;
	
	protected $refreshToken = null;
	
	protected $accessScope = array(
		self::SCOPE_ANALYTICS,
		self::SCOPE_USERINFO_EMAIL,
	);
	
	protected $storage;
	
	protected $accessType = 'offline';
	protected $approvalPrompt = 'force';

	protected $clientId;
	protected $clientSecret;
	
	protected $redirectUri;
	
	/**
	 * @var \Supra\RemoteHttp\RemoteHttpRequestService
	 */
	protected $httpService;
	
	
	public function __construct()
	{
		$this->httpService = new RemoteHttpRequestService();
	}
	
	public function setClientId($clientId)
	{
		$this->clientId = $clientId;
	}
	
	public function setClientSecret($clientSecret)
	{
		$this->clientSecret = $clientSecret;
	}
	
	public function setAccessType($accessType)
	{
		$this->accessType = $accessType;
	}
	
	public function setApprovalPrompt($approvalPrompt)
	{
		$this->approvalPrompt = $approvalPrompt;
	}
	
	public function setRedirectUri($redirectUri)
	{
		$this->redirectUri = $redirectUri;
	}
		
	public function setStorage(Storage\StorageInterface $storage)
	{
		$this->storage = $storage;
	}
	
	protected function setAccessToken($accessToken)
	{
		$this->accessToken = $accessToken;
	}
	
	public function getRefreshToken()
	{
		$this->loadStoredValues();
		
		return $this->refreshToken;
	}

	/**
	 * 
	 */
	protected function doRequest($url, $requestType, $params = array())
	{
		\Log::info("Requesting Google Authentication service by url {$url}");
		
		$request = new RemoteHttpRequest($url, $requestType, $params);
		
		$response = $this->httpService->makeRequest($request);
		
		return $response;
	}
	
	/**
	 * 
	 */
	public function sign(RemoteHttpRequest $request)
	{
		if ( ! $this->isAuthenticated()) {
			throw new \RuntimeException('Cannot sign request as access token is missing');
		}
		
		$request->header('Authorization', 'Bearer ' . $this->accessToken);
		return $request;
	}
	
	/**
	 * 
	 */
	public function authenticate($code)
	{
		$requestParams = array(
			'code' => $code,
			'grant_type' => 'authorization_code',
			'redirect_uri' => $this->redirectUri,
			'client_secret' => $this->clientSecret,
			'client_id' => $this->clientId,
		);
		
		$response = $this->doRequest(self::URL_OAUTH2_TOKEN, 'POST', $requestParams);
		
		$responseBody = $response->getBody();
		$decodedResponse = json_decode($responseBody, true);
		
		if ($response->getCode() !== 200 && ! empty($decodedResponse)) {
			$error = null;
			if ($decodedResponse && isset($decodedResponse['error'])) {
				$error = $decodedResponse['error'];
			}
			
			return false;
			//throw new \RuntimeException("Failed to obtain access token, error \"{$error}\"");
		}
			
		$this->accessToken = $decodedResponse['access_token'];
		$this->accessTokenExpiresIn = $decodedResponse['expires_in'];
		$this->accessTokenCreated = time();
		
		$this->refreshToken = $decodedResponse['refresh_token'];
		
		$this->storeTokenValues();
		
		return true;
	}
	
	/**
     *
	 */
	protected function doRefreshAccessToken($refreshToken)
	{
		$requestParams = array(
			'refresh_token' => $refreshToken,
			'grant_type' => 'refresh_token',
			'client_secret' => $this->clientSecret,
			'client_id' => $this->clientId,
		);
		
		$response = $this->doRequest(self::URL_OAUTH2_TOKEN, 'POST', $requestParams);
		
		if ($response->getCode() !== 200) {
			throw new \RuntimeException('Failed to refresh access token token');
		}
		
		$responseBody = $response->getBody();
		$decodedResponse = json_decode($responseBody, true);
		
		if ( ! (isset($decodedResponse['access_token']) 
				|| isset($decodedResponse['expires_in'])
				|| isset($decodedResponse['created']))) {
			
			throw new \RuntimeException('Wrong response when refreshing access token');
		}
		
		$this->accessToken = $decodedResponse['access_token'];
		$this->accessTokenExpiresIn = $decodedResponse['expires_in'];
		$this->accessTokenCreated = time();
		
		$this->storeTokenValues();
		
		return $this->accessToken;
	}
	
	/**
	 * 
	 */
	protected function loadStoredValues()
	{
		if ($this->loaded) {
			return;
		}
		
		$accessToken = $this->storage->get('access_token');
		$accessTokenExpiresIn = $this->storage->get('access_token_expires_in');
		$accessTokenCreated = $this->storage->get('access_token_created');

		$refreshToken = $this->storage->get('refresh_token');
		
		$this->accessToken = $accessToken;
		$this->accessTokenExpiresIn = $accessTokenExpiresIn;
		$this->accessTokenCreated = $accessTokenCreated;
		$this->refreshToken = $refreshToken;
		
		$this->loaded = true;
	}
	
	/**
	 * 
	 */
	protected function storeTokenValues()
	{
		$this->storage->set('access_token', $this->accessToken);
		$this->storage->set('access_token_expires_in', $this->accessTokenExpiresIn);
		$this->storage->set('access_token_created', $this->accessTokenCreated);
		$this->storage->set('refresh_token', $this->refreshToken);
		
		$this->storage->flush();
	}
	
	/**
	 * @return string
	 */
	public function createAuthorizationUrl($state = null)
	{
		$scopeString = implode(' ', $this->accessScope);
		
		$params = array(
			'response_type' => 'code',
			'redirect_uri' => $this->redirectUri,
			'client_id' => $this->clientId,
			'scope' => $scopeString,
			'access_type' => $this->accessType,
			'approval_prompt' => $this->approvalPrompt,
		);
		
		if ($state) {
			$params['state'] = $state;
		}
		
		$query = http_build_query($params);
		
		return self::URL_OAUTH2_AUTH . '?' . $query;
	}
	
	/**
	 * @return string
	 */
	public function isAuthenticated()
	{
		$token = $this->getAccessToken();
		return ( ! empty($token));
	}
	
	/**
	 * @return boolean
	 */
	public function unauthorize()
	{
		$this->accessToken = null;
		$this->accessTokenCreated = null;
		$this->refreshToken = null;
		$this->accessTokenExpires = null;
		
		$this->storeTokenValues();
		
		return true;
	}
	
	/**
	 * @return string|null
	 */
	public function getAccessToken()
	{
		$this->loadStoredValues();
		
		if ( ! empty($this->accessToken) && ! $this->isAccessTokenExpired()) {
			return $this->accessToken;
		}
		
		if ( ! empty($this->refreshToken)) {
			return $this->doRefreshAccessToken($this->refreshToken);
		}
		
		return null;
	}
	
	/**
	 * @return boolean
	 */
	private function isAccessTokenExpired()
	{
		// additional time will prevent token expiry 
		// between single session requests
		$expiryTime = time() + 120;
		
		if ( (int) $this->accessTokenExpiresIn + (int) $this->accessTokenCreated > $expiryTime) {
			return false;
		}
		return true;
	}
}