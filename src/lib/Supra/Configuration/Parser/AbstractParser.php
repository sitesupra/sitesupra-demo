<?php

namespace Supra\Configuration\Parser;

use Supra\Configuration\Exception;
//use Supra\ObjectRepository\ObjectRepository;
use Supra\Log\Writer\WriterAbstraction;

/**
 * Abstract configuration parser
 *
 */
abstract class AbstractParser implements ParserInterface
{
	/**
	 * @var WriterAbstraction
	 */
	protected $log;

	/**
	 * @var string
	 */
	protected $filename;
	
	/**
	 * Bind log writer
	 */
	public function __construct()
	{
//		$this->log = ObjectRepository::getLogger($this);
	}
	
	/**
	 * Parse config from file
	 *
	 * @param string $filename 
	 * @return array
	 */
	public function parseFile($filename)
	{
		if ( ! is_file($filename) || ! is_readable($filename)) {
			throw new Exception\FileNotFoundException(
					'Configuration file ' . $filename . 
					' does not exist or is not readable');
		}

		$this->filename = $filename;
		
		$contents = $this->parse($filename);
		
		$this->filename = null;
		
		return $contents;
	}
	
	/**
	 * @param string $filename
	 */
	abstract protected function parse($filename);
}
