<?php

namespace Supra\Locale\Storage;

use Supra\Controller\Request\RequestInterface,
		Supra\Controller\Response\ResponseInterface,
		Supra\Controller\Response\Http as HttpResponse,
		Supra\Locale\Exception,
		Supra\Http\Cookie as HttpCookie,
		Supra\Log\Logger;

/**
 * Stores the current locale in the cookie
 */
class Cookie extends StorageAbstraction
{
	/**
	 * Cookie name for the current locale storage
	 * @var string
	 */
	protected $cookieName = 'supra_language';

	/**
	 * Store the detected locale
	 * @param RequestInterface $request
	 * @param ResponseInterface $response
	 * @param string $localeIdentifier
	 */
	public function store(RequestInterface $request, ResponseInterface $response, $localeIdentifier)
	{
		if ( ! ($response instanceof HttpResponse)) {
			\Log::swarn("The response must be instance of Http response to use cookie storage");
			return;
			//throw new Exception("The response must be instance of Http response to use cookie storage");
		}
		
		$cookie = $this->createCookie($localeIdentifier);

		if (empty($cookie)) {
			Logger::swarn("Cookie not received from getCookie method in cookie locale storage");
			return false;
		}

		/* @var $response HttpResponse */
		$response->setCookie($cookie);
	}

	/**
	 * Creates cookie object for storing the current locale
	 * @param string $value
	 * @return HttpCookie
	 */
	protected function createCookie($value)
	{
		$cookie = new HttpCookie($name, $value);
		return $cookie;
	}
}