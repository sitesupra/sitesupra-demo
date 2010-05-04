<?php

namespace Supra\Controller\Response;

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

	protected $output = array();

	protected $redirect = false;

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

	public function prepare()
	{
		ob_end_clean();
		ob_start('ob_gzhandler');
	}

	public function header($name, $value, $replace = true)
	{
		$name = static::normalizeHeader($name);
		if ($replace || ! array_key_exists($name, $this->headers)) {
			$this->headers[$name] = array();
		}
		$this->headers[$name][] = $value;
	}

	protected function sendHeader($name)
	{
		$replace = true;
		foreach ($this->headers[$name] as $value) {
			header($name . ': ' . $value, $replace);
			$replace = false;
		}
	}

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
		if ( ! $this->isRedirect()) {
			echo implode('', $this->output);
		}
		ob_end_flush();
	}
}