<?php

namespace Supra\Configuration\Parser;

use Supra\Configuration\Exception;

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
		try {
			$contents = file_get_contents($filename);
			$data = $this->parseContents($contents);
		} catch (\Exception $e) {
			throw new Exception\RuntimeException("Could not parse configuration file $filename", null, $e);
		}
		
		return $data;
	}
	
	/**
	 * @param string $contents
	 * @return array
	 */
	abstract public function parseContents($contents);
}
