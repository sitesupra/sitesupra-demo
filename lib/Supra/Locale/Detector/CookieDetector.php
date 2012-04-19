<?php

namespace Supra\Locale\Detector;

use Supra\Request\RequestInterface;
use Supra\Response\ResponseInterface;
use Supra\Request\HttpRequest;

/**
 * Cookie locale detector
 */
class CookieDetector extends DetectorAbstraction
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
			\Log::warn('Request must be instance of Http request object to use cookie locale detection');
			return;
		}

		/* @var $request HttpRequest */
		$locale = $request->getCookie($this->cookieName, null);
		return $locale;
	}
}