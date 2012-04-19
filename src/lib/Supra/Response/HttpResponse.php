<?php

namespace Supra\Response;

use Supra\Http\Cookie;
use Supra\Response\ResponseContext;

/**
 * HTTP response object
 */
class HttpResponse implements ResponseInterface
{
	// Reserved array key for status header
	const STATUS_HEADER_NAME = '';

	const PROTOCOL = 'HTTP';

	const STATUS_OK = 200;
	const STATUS_NO_CONTENT = 204;
	const STATUS_MOVED_PERMANENTLY = 301;
	const STATUS_FOUND = 302;
	const STATUS_SEE_OTHER = 303;
	const STATUS_NOT_MODIFIED = 304;
	const STATUS_TEMPORARY_REDIRECT = 307;
	
	// Redirect types
	const REDIRECT_PERMAMENT = 301;
	const REDIRECT_TEMPORARY = 302;

	/**
	 * @var ResponseContext
	 */
	protected $context;

	/**
	 * Messages for HTTP status codes
	 * @var array
	 */
	protected static $messages = array(
		100 => 'Continue',
		101 => 'Switching Protocols',
		self::STATUS_OK => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		self::STATUS_NO_CONTENT => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',
		300 => 'Multiple Choices',
		self::STATUS_MOVED_PERMANENTLY => 'Moved Permanently',
		self::STATUS_FOUND => 'Found',
		self::STATUS_SEE_OTHER => 'See Other',
		self::STATUS_NOT_MODIFIED => 'Not Modified',
		305 => 'Use Proxy',
		self::STATUS_TEMPORARY_REDIRECT => 'Temporary Redirect',
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
	 * Status code
	 * @var int
	 */
	protected $code = self::STATUS_OK;

	/**
	 * Status code message
	 * @var string
	 */
	protected $message;

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
	 * Constructor
	 */
	public function __construct()
	{
		
	}
	
	/**
	 * Skippig new fields added by extending
	 * @return array
	 */
	public function __sleep()
	{
		$fields = get_class_vars(__CLASS__);
		
		//  $messages is STATIC and confuses serialze();
		unset($fields['messages']);
		
		$fieldNames = array_keys($fields);
		
		return $fieldNames;
	}

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

		$this->code = $code;
		$this->message = self::$messages[$code];
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
	public function redirect($location, $type = self::REDIRECT_TEMPORARY)
	{
		$this->redirect = true;
		$this->header('Location', $location);
		
		// Calculate status depending on HTTP version and redirect type
		$status = null;
		
		if ($this->protocolVersion == '1.1') {
			if ($type == self::REDIRECT_PERMAMENT) {
				$status = self::STATUS_MOVED_PERMANENTLY;
			} else {
				$status = self::STATUS_SEE_OTHER;
			}
		} else {
			if ($type == self::REDIRECT_PERMAMENT) {
				$status = self::STATUS_MOVED_PERMANENTLY;
			} else {
				$status = self::STATUS_FOUND;
			}
		}
		
		$this->setCode($status);
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
	 * Returns if response might have content
	 * @return boolean
	 */
	public function hasOutput()
	{
		$hasOutput = true;

		if ($this->isRedirect()) {
			$hasOutput = false;
		}

		if ($this->code == self::STATUS_NO_CONTENT || $this->code == self::STATUS_NOT_MODIFIED) {
			$hasOutput = false;
		}

		return $hasOutput;
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
	 * Get output as string
	 * @return string
	 */
	public function getOutputString()
	{
		return $this->__toString();
	}

	/**
	 * Get output as string
	 * @return string
	 */
	public function __toString()
	{
		return implode('', $this->output);
	}

	/**
	 * Send the headers and output the content
	 */
	public function flush()
	{
		// Don't send status header if is 200
		if ($this->code != self::STATUS_OK) {

			$statusHeader = self::PROTOCOL . '/' . $this->protocolVersion . ' '
					. $this->code . ' ' . $this->message;

			$this->header(self::STATUS_HEADER_NAME, $statusHeader);
		}

		foreach ($this->headers as $name => $values) {
			$this->sendHeader($name);
		}
		$this->headers = array();

		foreach ($this->cookies as $cookie) {
			$this->sendCookie($cookie);
		}
		$this->cookies = array();

		if ($this->hasOutput()) {

			foreach ($this->output as $output) {
				if ($output instanceof HttpResponse) {
					$output->flush();
				} else {
					echo $output;
				}
			}
		}

		$this->output = array();
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

		// Overwrites response code only if higher and resets (TODO: is it correct way to do?)
		if ($this->code > $response->code) {
			$response->setCode($this->code);
		}
		$this->code = self::STATUS_OK;

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

		// Send the whole response object to the output array
		$response->output($this);
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

	/**
	 * Helper method to increase the odds noone will cache the response
	 */
	public function forbidCache()
	{
		$this->header("Expires", gmdate("r", 0));
		$this->header("Last-Modified", gmdate("r"));
		$this->header("Cache-Control", "no-store, no-cache, must-revalidate, max-age=0");
		$this->header("Cache-Control", "post-check=0, pre-check=0", false);
		$this->header("Pragma", "no-cache");
	}

	/**
	 * @param ResponseContext $context 
	 */
	public function setContext(ResponseContext $context = null)
	{
		if ( ! empty($context)) {
			$this->context = $context;
		}
	}

	/**
	 * @return ResponseContext
	 */
	public function getContext()
	{
		if (empty($this->context)) {
			$this->context = new ResponseContext();
		}
		
		return $this->context;
	}
	
	/**
	 * Add resource file used for generating the response
	 * @param string $file
	 */
	public function addResourceFile($file)
	{
		// It's available only for blocks with local context proxy
		//TODO: might store in both places – all resources for global context, local for local context
		if ( ! $this->context instanceof ResponseContextLocalProxy) {
			return;
		}
		
		$this->context->addResourceFile($file);
	}
	
	/**
	 * Get resource file list used for generating the response
	 * @return array
	 */
	public function getResourceFiles()
	{
		// It's available only for blocks with local context proxy
		//TODO: might store in both places – all resources for global context, local for local context
		if ( ! $this->context instanceof ResponseContextLocalProxy) {
			return array();
		}
		
		$resources = $this->context->getResourceFiles();
		
		return $resources;
	}
	
	/**
	 * @return boolean
	 */
	public function isResourceChanged()
	{
		if ( ! $this->context instanceof ResponseContextLocalProxy) {
			return false;
		}
		
		return $this->context->isResourceChanged();
	}
}
