<?php

namespace Supra\RemoteHttp;

class RemoteHttpRequestService
{
	
	/**
	 * @var \Supra\RemoteHttp\Adapter\RemoteHttpAdapterInterface
	 */
	protected $adapter;
	
	
	/**
	 * @param \Supra\RemoteHttp\Request\RemoteHttpRequest $request
	 * @return \Supra\RemoteHttp\Response\RemoteHttpResponse
	 */
	public function makeRequest(Request\RemoteHttpRequest $request)
	{
		$adapter = $this->getIOAdapter();
		return $adapter->makeRequest($request);
	}
	
	/**
	 * @param \Supra\RemoteHttp\Adapter\RemoteHttpAdapterInterface $adapter
	 */
	public function setIOAdapter(Adapter\RemoteHttpAdapterInterface $adapter)
	{
		$this->adapter = $adapter;
	}
	
	/**
	 * @return \Supra\RemoteHttp\Adapter\RemoteHttpAdapterInterface
	 */
	public function getIOAdapter()
	{
		if (is_null($this->adapter)) {
			$this->autoLoadAdapter();
		}
		
		return $this->adapter;
	}
	
	/**
	 * Tries to autodetect which adapter to use
	 * Throws exception on failure
	 */
	private function autoLoadAdapter()
	{
		if (function_exists('curl_version')) {
			$this->adapter = new Adapter\CurlHttpAdapter();
		}
		else if (ini_get('allow_url_fopen') == '1') {
			$this->adapter = new Adapter\PhpFopenAdapter();
		}
		else {
			throw new \RuntimeException('Failed to autodetect remote HTTP service adapter');
		}
	}
}