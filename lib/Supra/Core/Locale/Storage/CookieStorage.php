<?php

namespace Supra\Core\Locale\Storage;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Stores the current locale in the cookie
 */
class CookieStorage implements StorageInterface
{
	/**
	 * Cookie name for the current locale storage
	 * @var string
	 */
	protected $cookieName = 'supra_language';

	/**
	 * Store the detected locale.
	 *
	 * @param Request $request
	 * @param Response $response
	 * @param string $localeId
	 */
	public function store(Request $request, Response $response, $localeId)
	{
		$response->headers->setCookie(new Cookie($this->cookieName, $localeId));
	}
}