<?php

namespace Supra\Configuration\Parser;

use Supra\Configuration\Exception;

/**
 * Abstract configuration parser
 *
 */
abstract class AbstractParser implements ParserInterface
{

	/**
	 * Filename
	 *
	 * @var string
	 */
	protected $filename = '';
	
	/**
	 * Parse config from file
	 *
	 * @param string $filename 
	 */
	public function parseFile($filename)
	{
		if ( ! \is_file($filename) || ! \is_readable($filename)) {
			throw new Exception\FileNotFoundException(
					'Configuration file ' . $filename . 
					' does not exist or is not readable');
		}
		$this->filename = $filename;
		$contents = \file_get_contents($filename);
		$this->parse($contents);
		$this->filename = '';
	}

	/**
	 * Log warn
	 *
	 * @param string $message 
	 */
	protected function logWarn($message)
	{
		if ( ! empty($this->filename)) {
			$message = $message . ' (file: '. $this->filename . ')';
		}
		\Log::warn($message);
	}
	
}
