<?php

namespace Supra\Configuration\Parser;

/**
 * Configuration parser interface
 *
 */
interface ParserInterface 
{
	/**
	 * Parse data from file
	 * 
	 * @param string $filename
	 * @return array
	 */
	public function parseFile($filename);
}
