<?php

namespace Supra\Controller\Response;

use Supra\Http\Cookie;

/**
 * Description of Http
 */
class Http implements ResponseInterface
{
	/**
	 * Headers
	 * @var array
	 */
	protected $headers = array();

	/**
	 * Output data
	 * @var array
	 */
	protected $output = array();

	/**
	 * Whether the response is redirect
	 * @var boolean
	 */
	protected $redirect = false;

	/**
	 * Cookies
	 * @var Cookie[]
	 */
	protected $cookies = array();

	/**
	 * Normalizes header name
	 * @param string $name
	 * @return string
	 */
	protected static function normalizeHeader($name)
    {
        $name = str_replace(array('-', '_'), ' ', $name);
        $name = ucwords(strtolower($name));
        $name = str_replace(' ', '-', $name);
        return $name;
    }

	/**
	 * Response prepare method
	 */
	public function prepare()
	{
		ob_end_clean();
		ob_start('ob_gzhandler');
	}

	/**
	 * Stores header data
	 */
	public function header($name, $value, $replace = true)
	{
		$name = static::normalizeHeader($name);
		if ($replace || ! array_key_exists($name, $this->headers)) {
			$this->headers[$name] = array();
		}
		$this->headers[$name][] = $value;
	}

	/**
	 * Sends the header to client
	 * @param string $name
	 */
	protected function sendHeader($name)
	{
		$replace = true;
		foreach ($this->headers[$name] as $value) {
			header($name . ': ' . $value, $replace);
			$replace = false;
		}
	}

	/**
	 * Removes the header specified from the buffer
	 * @param string $name
	 */
	public function removeHeader($name)
	{
		$name = static::normalizeHeader($name);
		unset($this->headers[$name]);
		if ($name == static::normalizeHeader('Location')) {
			$this->redirect = false;
		}
	}

	/**
	 * Redirect response
	 * @param string $location
	 */
	public function redirect($location)
	{
		$this->redirect = true;
		$this->header('Location', $location);
	}

	/**
	 * Cleans redirect if set
	 */
	public function cleanRedirect()
	{
		$this->removeHeader('Location');
		$this->redirect = false;
	}

	/**
	 * Whether the result is redirection
	 * @return boolean
	 */
	public function isRedirect()
	{
		return $this->redirect;
	}

	/**
	 * Add output to the buffer
	 * @param string $output
	 */
	public function output($output)
	{
		$this->output[] = $output;
	}

	/**
	 * Clean output buffer
	 */
	public function cleanOutput()
	{
		$this->output = array();
	}

	/**
	 * Send the headers and output the content
	 */
	public function flush()
	{
		foreach ($this->headers as $name => $values) {
			$this->sendHeader($name);
		}
		$this->headers = array();
		
		foreach ($this->cookies as $cookie) {
			$this->sendCookie($cookie);
		}
		$this->cookies = array();

		if ( ! $this->isRedirect()) {
			echo implode('', $this->output);
		}
		$this->output = array();
		
		ob_end_flush();
	}

	/**
	 * Set cookie
	 * @param Cookie $cookie
	 */
	public function setCookie(Cookie $cookie)
	{
		$this->cookies[] = $cookie;
	}

	/**
	 * Send cookie to the client
	 * @param Cookie $cookie
	 */
	public function sendCookie(Cookie $cookie)
	{
		$cookie->send();
	}
}