<?php

namespace Supra\Configuration;

/**
 * Includes additional configuration from static files
 */
abstract class IncludeConfiguration implements ConfigurationInterface
{
	/**
	 * Base directory for files
	 * @var string
	 */
	public $baseDir;
	
	/**
	 * List of files
	 * @var array
	 */
	public $files = array();
	
	public function configure()
	{
		foreach ($this->files as $file) {
			// Skip empty filenames
			if ($file == '') {
				continue;
			}

			// Prepend base directory
			if ($this->baseDir != '') {
				$file = $this->baseDir . DIRECTORY_SEPARATOR . $file;
			}
			
			// If relative, add supra path as a base
			if ($file[0] != '/' && strpos($file, ':') !== false) {
				$file = SUPRA_PATH . $file;
			}
			
			$this->parseFile($file);
		}
	}
	
	/**
	 * @param string $file
	 */
	abstract protected function parseFile($file);
}
