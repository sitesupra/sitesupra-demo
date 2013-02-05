<?php

namespace Supra\RemoteHttp\Adapter;

use Supra\RemoteHttp\Response\RemoteHttpResponse;
use Supra\RemoteHttp\Request\RemoteHttpRequest;


class CurlHttpAdapter implements RemoteHttpAdapterInterface
{
	/**
	 * @var array
	 */
	protected $curlOptions = array (
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_FOLLOWLOCATION => false,
		CURLOPT_FAILONERROR => false,
		CURLOPT_SSL_VERIFYPEER => true,
		CURLOPT_HEADER => true,
		CURLOPT_VERBOSE => false,
		CURLOPT_USERAGENT => 'cURL/PHP',
	);
	
	/**
	 * @throws \RuntimeException
	 */
	public function __construct()
	{
		if ( ! function_exists('curl_version')) {
			throw new \RuntimeException('cURL extension is not installed or is disabled');
		}
	}
	
	/**
	 * @param string $name
	 * @param mixed $value
	 */
	public function setCurlOption($name, $value)
	{
		$this->curlOptions[$name] = $value;
	}
	
	/**
	 * @param \Supra\RemoteHttp\Request\RemoteHttpRequest $request
	 * @return \Supra\RemoteHttp\Response\RemoteHttpResponse
	 * @throws \RuntimeException
	 */
	public function makeRequest(RemoteHttpRequest $request) 
	{
		$url = $request->getUrl();
		
		$handle = curl_init();
			
		curl_setopt_array($handle, $this->curlOptions);
		
		$requestHeaders = $request->getHeaders();
		
		if ( ! empty($requestHeaders)) {
			$headers = array();
			foreach($requestHeaders as $name => $value) {
				$headers[] = "$name: $value";
			}
			
			curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
		}

		$isPost = $request->getType() == RemoteHttpRequest::TYPE_POST;
		
		curl_setopt($handle, CURLOPT_POST, $isPost);

		$requestParams = $request->getRequestParameters();
		if ( ! empty($requestParams)) {
			
			$parameterString = http_build_query($requestParams);
			if ($isPost) {
				curl_setopt($handle, CURLOPT_POSTFIELDS, $parameterString);
			} else {
				
				//@FIXME: this is wrong
				$url = $url . '?' . $parameterString;
			}
		}
			
		curl_setopt($handle, CURLOPT_URL, $url);
		
		$rawResponse = curl_exec($handle);
		$responseCode = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
		
		$errorNum = curl_errno($handle);
		$curlError = curl_error($handle);
		
		$headerSize = curl_getinfo($handle, CURLINFO_HEADER_SIZE);
		
		$rawHeaders = substr($rawResponse, 0, $headerSize);
		$rawBody = substr($rawResponse, $headerSize);
		
		curl_close($handle);
		
		if ($errorNum !== CURLE_OK) {
			throw new \RuntimeException("cURL request failed with error {$errorNum}: {$curlError}");
		}
		
		$response = new RemoteHttpResponse();
		$response->setHttpCode($responseCode);
		$response->setBody($rawBody);
		
		return $response;
	}
}