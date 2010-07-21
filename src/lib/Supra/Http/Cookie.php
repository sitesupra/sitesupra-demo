<?php

namespace Supra\Http;

use Supra\Log\Logger;

/**
 * Cookie class
 */
class Cookie
{
	/**
	 * Default expire regognizable by strtotime
	 * @var string
	 */
	static $defaultExpire = '+30 days';

	/**
	 * Cookie name
	 * @var string
	 */
	protected $name;

	/**
	 * Cookie value
	 * @var string
	 */
	protected $value = '';

	/**
	 * Cookie expire timestamp
	 * @var int
	 */
	protected $expire;

	/**
	 * The path on the server in which the cookie will be available on
	 * @var string
	 */
	protected $path = '/';

	/**
	 * The domain that the cookie is available
	 * @var string
	 */
	protected $domain;

	/**
	 * Indicates that the cookie should only be transmitted over a secure
	 * HTTPS connection from the client
	 * @var boolean
	 */
	protected $secure = false;

	/**
	 * When TRUE the cookie will be made accessible only through the HTTP protocol
	 * @var boolean
	 */
	protected $httpOnly = false;

	/**
	 * Constructor
	 * @param string $name
	 * @param string $value
	 */
	public function __construct($name, $value = '')
	{
		$this->setName($name);
		$this->setValue($value);
	}

	/**
	 * Sets name
	 * @param string $name
	 */
	public function setName($name)
	{
		$this->name = $name;
	}

	/**
	 * Gets name
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Sets value
	 * @param string $value
	 */
	public function setValue($value)
	{
		$this->value = $value;
	}

	/**
	 * Gets value
	 * @return string
	 */
	public function getValue()
	{
		return $this->value;
	}

	/**
	 * Sets expire by unix timestamp or string compatible with strtotime
	 * @param integer|string $expire
	 * @return boolean of success
	 */
	public function setExpire($expire = null)
	{
		if (\is_string($expire)) {
			$expireParsed = \strtotime($expire);
			if (empty($expire)) {
				Logger::swarn("Invalid expire string '$expire' provided as cookie expire time");
				return false;
			}
			$expire = $expireParsed;
		}
		if (is_null($expire)) {
			$this->expire = null;
		}
		if ( ! is_int($expire)) {
			$expireType = \gettype($expire);
			Logger::swarn("Invalid expire type '{$expireType}' provided as cookie expire time");
			return false;
		}
		$this->expire = $expire;
		return true;
	}

	/**
	 * Gets expire timestamp, default is used if not set
	 * @return integer
	 */
	public function getExpire()
	{
		if (is_null($this->expire)) {
			return strtotime(static::$defaultExpire);
		}
		return $this->expire;
	}

	/**
	 * Sets path
	 * @param string $path
	 */
	public function setPath($path)
	{
		$this->path = $path;
	}

	/**
	 * Gets path
	 * @return string
	 */
	public function getPath()
	{
		return $this->path;
	}

	/**
	 * Sets domain
	 * @param string $domain
	 */
	public function setDomain($domain)
	{
		$this->domain = $domain;
	}

	/**
	 * Gets domain
	 * @return string
	 */
	public function getDomain()
	{
		return $this->domain;
	}

	/**
	 * Sets secure parameter
	 * @param boolean $secure
	 */
	public function setSecure($secure = true)
	{
		$this->secure = $secure;
	}

	/**
	 * Gets secure parameter
	 * @return boolean
	 */
	public function getSecure()
	{
		return $this->secure;
	}

	/**
	 * Sets httpOnly parameter
	 * @param boolean $httpOnly
	 */
	public function setHttpOnly($httpOnly = true)
	{
		$this->httpOnly = $httpOnly;
	}

	/**
	 * Gets httpOnly parameter
	 * @return boolean
	 */
	public function getHttpOnly()
	{
		return $this->httpOnly;
	}

	/**
	 * Send the cookie to the client
	 */
	public function send()
	{
		// If null, set default expiration
		\setcookie(
				$this->getName(),
				$this->getValue(),
				$this->getExpire(),
				$this->getPath(),
				$this->getDomain(),
				$this->getSecure(),
				$this->getHttpOnly());
	}

}