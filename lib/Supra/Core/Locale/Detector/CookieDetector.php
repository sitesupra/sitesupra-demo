<?php

namespace Supra\Core\Locale\Detector;

use Symfony\Component\HttpFoundation\Request;

/**
 * Cookie locale detector
 */
class CookieDetector implements DetectorInterface
{
	/**
	 * Cookie name for the current locale storage
	 * @var string
	 */
	protected $cookieName = 'supra_language';

	/**
	 * @param string|null $cookieName
	 */
	public function __construct($cookieName = 'supra_language')
	{
		$this->cookieName = $cookieName;
	}

	/**
	 * Searches for the current locale in cookies.
	 *
	 * @param \Symfony\Component\HttpFoundation\Request $request
	 * @return string
	 */
	public function detect(Request $request)
	{
		return $request->cookies->get($this->cookieName, null);
	}
}