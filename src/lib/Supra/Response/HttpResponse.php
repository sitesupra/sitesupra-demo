<?php

namespace Supra\Response;

use Supra\Http\Cookie;

/**
 * HTTP response object
 */
class HttpResponse implements ResponseInterface
{
	// Reserved array key for status header
	const STATUS_HEADER_NAME = '';
	
	const PROTOCOL = 'HTTP';
	
	/**
	 * Messages for HTTP status codes
	 * @var array
	 */
	protected static $messages = array(
		100 => 'Continue',
		101 => 'Switching Protocols',

		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',

		300 => 'Multiple Choices',
		301 => 'Moved Permanently',
		302 => 'Found',
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		307 => 'Temporary Redirect',

		400 => 'Bad Request',
		401 => 'Unauthorized',
		402 => 'Payment Required',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Timeout',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Request Entity Too Large',
		414 => 'Request-URI Too Long',
		415 => 'Unsupported Media Type',
		416 => 'Requested Range Not Satisfiable',
		417 => 'Expectation Failed',

		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Timeout',
		505 => 'HTTP Version Not Supported',
		509 => 'Bandwidth Limit Exceeded'
	);
	
	/**
	 * Flag that output buffering has been started
	 * @var boolean
	 */
	protected static $gzOutputBufferingStarted = false;

	/**
	 * Server protocol
	 */
	protected $protocolVersion = '1.0';
	
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
	 * Set response status code
	 * @param int $code
	 */
	public function setCode($code)
	{
		$code = (int) $code;
		
		if ( ! isset(self::$messages[$code])) {
			throw new Exception\RuntimeException("Code $code is not known to the HttpResponse class");
		}
		
		$message = self::$messages[$code];
		$statusHeader = self::PROTOCOL . '/' . $this->protocolVersion . ' ' 
				. $code . ' ' . $message;
		
		// Empty key is reserved for the status
		$this->header(self::STATUS_HEADER_NAME, $statusHeader);
	}

	/**
	 * Stores header data
	 * @param string $name
	 * @param string $value
	 * @param boolean $replace
	 */
	public function header($name, $value, $replace = true)
	{
		if ($name != self::STATUS_HEADER_NAME) {
			$name = static::normalizeHeader($name);
		}
		
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
			if ($name == self::STATUS_HEADER_NAME) {
				header($data['value']);
			} else {
				header($name . ': ' . $data['value'], $data['replace']);
			}
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
		
		// Remove redirect flag
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
	 * @param ResponseInterface $response
	 */
	public function flushToResponse(ResponseInterface $response)
	{
		if ( ! ($response instanceof HttpResponse)) {
			throw new Exception\IncompatibleObject("The response object passed to Response\HttpResponse::flushToResponse() must be compatible with the source object");
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