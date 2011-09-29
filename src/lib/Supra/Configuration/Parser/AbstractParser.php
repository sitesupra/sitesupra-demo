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
		$this->parse($filename);
	}
	
}
