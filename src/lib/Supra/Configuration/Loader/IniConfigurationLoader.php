<?php

namespace Supra\Configuration\Loader;

use Supra\Configuration\Parser\IniParser;
use Supra\Configuration\Exception;
use Supra\Configuration\Parser;

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
	protected $data = null;

	/**
	 * @var Parser\AbstractParser
	 */
	protected $parser;

	/**
	 * @param string $filename
	 * @param string $directory
	 */
	public function __construct($filename, $directory = SUPRA_CONF_PATH)
	{
 		$this->filename = $directory . DIRECTORY_SEPARATOR . $filename;
	}

	/**
	 * @return Parser\AbstractParser
	 */
	public function getParser()
	{
		if (empty($this->parser)) {
			$this->parser = new IniParser();
		}

		return $this->parser;
	}

	/**
	 * @param Parser\AbstractParser $parser 
	 */
	public function setParser(Parser\AbstractParser $parser)
	{
		$this->parser = $parser;
	}

	protected function parse()
	{
		$parser = $this->getParser();

		$data = $parser->parseFile($this->filename);

		$this->data = $data;
	}

	/**
	 * Get all data
	 * @return array
	 */
	public function getData()
	{
		if (is_null($this->data)) {
			$this->parse();
		}

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
		$data = $this->getData();
		
		if (isset($data[$section][$key])) {
			return $data[$section][$key];
		}

		if (func_num_args() > 2) {
			return $default;
		}

		throw new Exception\ConfigurationMissing("Section '$section' value '$key' not configured in file '{$this->filename}'");
	}

	/**
	 * @param string $section
	 * @return boolean
	 */
	public function hasSection($section)
	{
		$data = $this->getData();

		if (isset($data[$section])) {
			return true;
		}

		return false;
	}
    
    
	/**
	 * @param string $section
     * @param string $key
	 * @return boolean
	 */
	public function hasKey($section, $key)
	{        
		$data = $this->getData();

		if (isset($data[$section][$key])) {
			return true;
		}

		return false;
	}

	/**
	 * @param string $section
	 * @param string $default
	 * @return mixed
	 * @throws Exception\ConfigurationMissing
	 */
	public function getSection($section, $default = null)
	{
		$data = $this->getData();
		
		if (isset($data[$section])) {
			return $data[$section];
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
