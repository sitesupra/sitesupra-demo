<?php

namespace Supra;

use Supra\Configuration\Exception\ConfigurationMissing;
use Supra\Configuration\Exception\InvalidConfiguration;
use Supra\ObjectRepository\ObjectRepository;

class Info implements Configuration\ConfigurationInterface
{

	const NO_SCHEME = 1;
	const WITH_SCHEME = 2;

	/**
	 * Site ID for remote communication
	 * @var string
	 */
	public $id;

	/**
	 * Project name
	 * @var string
	 */
	public $name = 'Project';

	/**
	 * Project version
	 * @var string 
	 */
	public $version = '1.0';

	/**
	 * Hostname
	 * @var string
	 */
	public $hostName;

	/**
	 * Webserver port
	 * @var string
	 */
	public $webserevrPort;

	public function configure()
	{
		// fetching data from supra.ini
		$conf = ObjectRepository::getIniConfigurationLoader('');
		$this->id = $conf->getValue('system', 'id', null);
		$this->hostName = $conf->getValue('system', 'host');
		$this->name = $conf->getValue('system', 'name');
		$this->webserverPort = $conf->getValue('system', 'webserver_port', '80');

		$version = '1.0';

		$versionPath = dirname(SUPRA_PATH) . DIRECTORY_SEPARATOR . 'VERSION';
		if (file_exists($versionPath)) {
			$versionNumber = trim(file_get_contents($versionPath));
			if ( ! empty($versionNumber)) {
				$version = $versionNumber;
			}
		}

		$this->version = $version;

		ObjectRepository::setDefaultSystemInfo($this);
	}

	/**
	 * @param int $format
	 * @return string
	 */
	public function getHostName($format = self::NO_SCHEME)
	{
		if (empty($this->hostName)) {
			throw new ConfigurationMissing("Host name not configured for the system");
		}

		$hostname = $this->hostName;

		$url = parse_url($hostname);

		if ( ! isset($url['scheme'])) {
			$hostname = 'http://' . $hostname;
		}

		$url = parse_url($hostname);

		$string = null;

		if (isset($url['host'])) {
			// Glue the URL
			$string = (isset($url['user']) ? $url['user'] . (isset($url['pass']) ? ':' . $url['pass'] : '') . '@' : '')
					. $url['host']
					. (isset($url['port']) ? ':' . $url['port'] : '');
		} else {
			throw new InvalidConfiguration("Invalid system hostname value");
		}

		if ($format == self::WITH_SCHEME) {
			$string = $url['scheme'] . '://' . $string;
		}

		return $string;
	}

	/**
	 * @param boolean $empty_on_default
	 * @return string
	 */
	public function getWebserverPort($empty_on_default = true)
	{
		$webserverPort = $empty_on_default ? '' : '80';

		if ( ! empty($this->webserverPort)) {

			if ($this->webserverPort != 80) {
				$webserverPort = $this->webserverPort;
			}
		}

		return $webserverPort;
	}

	public function getWebserverHostAndPort($format = self::NO_SCHEME)
	{
		$hostAndPort = $this->getHostName($format);

		$port = $this->getWebserverPort();

		if ( ! empty($port)) {
			$hostAndPort = $hostAndPort . ':' . $port;
		}

		return $hostAndPort;
	}

	public function getSystemId()
	{
		return md5($this->name) . '_' . $this->version;
	}

	/**
	 * Returns remote site id 
	 * @return string
	 */
	public function getSiteId()
	{
		return $this->id;
	}

}