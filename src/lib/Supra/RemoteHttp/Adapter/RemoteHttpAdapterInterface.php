<?php

namespace Supra\RemoteHttp\Adapter;

interface RemoteHttpAdapterInterface 
{
	/**
	 * @param \Supra\RemoteHttp\Request\RemoteHttpRequest $request
	 */
	public function makeRequest(\Supra\RemoteHttp\Request\RemoteHttpRequest $request);
}