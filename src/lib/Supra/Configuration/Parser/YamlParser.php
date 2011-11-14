<?php

namespace Supra\Configuration\Parser;

use Symfony\Component\Yaml\Yaml;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Loader;

/**
 * YAML configuration parser
 */
class YamlParser extends FileContentParser
{
	/**
	 * Parse YAML config
	 * @param string $contents
	 */
	public function parseContents($contents) 
	{
		$data = Yaml::parse($contents);

		return $data;
	}
}
