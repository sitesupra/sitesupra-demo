<?php

namespace Supra\Controller\Response;

use Supra\Http\Cookie,
		Supra\Controller\Exception;

/**
 * Description of Http
 */
class Http implements ResponseInterface
{
	protected static $gzOutputBufferingStarted = false;

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
		if ( ! self::$gzOutputBufferingStarted) {
			ob_end_clean();
			ob_start('ob_gzhandler');
			self::$gzOutputBufferingStarted = true;
		}
	}

	/**
	 * Stores header data
	 * @param string $name
	 * @param string $value
	 * @param boolean $replace
	 */
	public function header($name, $value, $replace = true)
	{
		$name = static::normalizeHeader($name);
		if ($replace || ! array_key_exists($name, $this->headers)) {
			$this->headers[$name] = array();
		}
		$this->headers[$name][] = array('value' => $value, 'replace' => $replace);
	}

	/**
	 * Sends the header to client
	 * @param string $name
	 */
	protected function sendHeader($name)
	{
		foreach ($this->headers[$name] as $data) {
			header($name . ': ' . $data['value'], $data['replace']);
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
	 * Get output string
	 * @return string
	 */
	public function getOutput()
	{
		return implode('', $this->output);
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
	 * Flush this response to the parent response
	 * @param Http $response
	 */
	public function flushToResponse(ResponseInterface $response)
	{
		if ( ! ($response instanceof Http)) {
			throw new Exception("The response object passed to Response\Http::flushToResponse() must be compatible with the source object");
		}
		
		foreach ($this->headers as $name => $headers) {
			foreach ($headers as $headerData) {
				$response->header($name, $headerData['value'], $headerData['replace']);
			}
		}
		$this->headers = array();

		foreach ($this->cookies as $cookie) {
			$response->setCookie($cookie);
		}
		$this->cookies = array();

		$response->output(implode('', $this->output));
		$this->output = array();
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