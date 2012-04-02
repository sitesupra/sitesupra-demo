<?php

namespace Supra\Configuration\Loader;

use Supra\Configuration\Parser\IniParser;
use Supra\Configuration\Exception;

/**
 * INI Configuratio file loader class (used for supra.ini)
 */
class IniConfigurationLoader
{
	/**
	 * @var string
	 */
	protected $filename;

	/**
	 * @var array
	 */
	protected $data;

	/**
	 * @param string $filename
	 * @param string $directory
	 */
	public function __construct($filename, $directory = SUPRA_CONF_PATH)
	{
		$this->filename = $directory . DIRECTORY_SEPARATOR . $filename;

		$parser = new IniParser();
		$data = $parser->parseFile($this->filename);

		$this->data = $data;
	}

	/**
	 * Get all data
	 * @return array
	 */
	public function getData()
	{
		return $this->data;
	}

	/**
	 * @param string $section
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 * @throws Exception\ConfigurationMissing
	 */
	public function getValue($section, $key, $default = null)
	{
		if (isset($this->data[$section][$key])) {
			return $this->data[$section][$key];
		}

		if (func_num_args() > 2) {
			return $default;
		}

		throw new Exception\ConfigurationMissing("Section '$section' value '$key' not configured in file '{$this->filename}'");
	}

	/**
	 * @param string $section
	 * @param string $default
	 * @return mixed
	 * @throws Exception\ConfigurationMissing
	 */
	public function getSection($section, $default = null)
	{
		if (isset($this->data[$section])) {
			return $this->data[$section];
		}

		if ( ! is_null($default)) {
			return $default;
		}

		throw new Exception\ConfigurationMissing("Section '$section' not configured in file '{$this->filename}'");
	}

	/**
	 * @return string
	 */
	public function getFilename()
	{
		return $this->filename;
	}

}
