<?php

namespace Supra\Configuration\Parser;

/**
 * Configuration parser interface
 *
 */
interface ParserInterface 
{
	/**
	 * Parse data
	 * 
	 * @param string $input
	 */
	public function parse($input);

	/**
	 * Parse data from file
	 * 
	 * @param string $filename
	 */
	public function parseFile($filename);
}
