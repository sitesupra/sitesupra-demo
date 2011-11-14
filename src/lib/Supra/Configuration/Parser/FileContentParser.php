<?php

namespace Supra\Configuration\Parser;

/**
 * Parser which works with file content
 */
abstract class FileContentParser extends AbstractParser
{
	/**
	 * @param string $filename
	 * @return array
	 */
	protected function parse($filename)
	{
		$contents = file_get_contents($filename);
		$data = $this->parseContents($contents);
		
		return $data;
	}
	
	/**
	 * @param string $contents
	 * @return array
	 */
	abstract function parseContents($contents);
}
