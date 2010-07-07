<?php

namespace Supra\Locale\Detector;

use Supra\Controller\Request\RequestInterface,
		Supra\Controller\Response\ResponseInterface,
		Supra\Controller\Request\Http as HttpRequest;

/**
 * Cookie locale detector
 */
class Cookie extends DetectorAbstraction
{
	/**
	 * Cookie name for the current locale storage
	 * @var string
	 */
	protected $cookieName = 'supra_language';

	/**
	 * Set cookie name
	 * @param string $cookieName
	 */
	public function setCookieName($cookieName)
	{
		$this->cookieName = $cookieName;
	}

	/**
	 * Detects the current locale
	 * @param RequestInterface $request
	 * @param ResponseInterface $response
	 * @return string
	 */
	public function detect(RequestInterface $request, ResponseInterface $response)
	{
		if ( ! ($request instanceof HttpRequest)) {
			\Log::swarn('Request must be instance of Http request object to use cookie locale detection');
			return;
		}

		/* @var $request \Supra\Controller\Request\Http */
		$locale = $request->getCookie($this->cookieName, null);
		return $locale;
	}
}