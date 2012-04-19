<?php

namespace Supra\Configuration\Parser;

/**
 * INI file parser
 */
class IniParser extends AbstractParser
{
	/**
	 * parse_ini_file 2nd argument
	 * @var boolean
	 */
	private $processSections;
	
	/**
	 * parse_ini_file 3rd argument
	 * @var int
	 */
	private $scannerMode;
	
	/**
	 * @param boolean $processSections
	 * @param int $scannerMode 
	 */
	public function __construct($processSections = true, $scannerMode = INI_SCANNER_NORMAL)
	{
		$this->processSections = $processSections;
		$this->scannerMode = $scannerMode;
	}
	
	/**
	 * Parse file passed
	 * @param string $filename
	 */
	public function parse($filename)
	{
		$data = parse_ini_file($filename, $this->processSections, $this->scannerMode);
		
		return $data;
	}
}
