<?php

namespace Supra\Core\Locale\Detector;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cookie locale detector
 */
class CookieDetector extends AbstractDetector
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
	 * @param \Symfony\Component\HttpFoundation\Request $request
	 * @param \Symfony\Component\HttpFoundation\Response $response
	 * @return string
	 */
	public function detect(Request $request, Response $response)
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