<?php

namespace Supra\Upgrade;

use \SplFileInfo;

/**
 * Upgrade file metadata
 */
abstract class UpgradeFileAbstraction extends SplFileInfo
{
	const CN = __CLASS__;
	
	/**
	 * @var string
	 */
	private $shortPath;
	
	/**
	 * @var string
	 */
	private $contents;
	
	/**
	 * @param string $shortPath
	 */
	public function setShortPath($shortPath)
	{
		$this->shortPath = $shortPath;
	}
	
	/**
	 * @return string
	 */
	public function getShortPath()
	{
		return $this->shortPath;
	}
	
	/**
	 * @return string
	 */
	public function getContents()
	{
		if (is_null($this->contents)) {
			$this->contents = file_get_contents($this->getPathname());
		}
		
		return $this->contents;
	}
	
	/**
	 * Fetches annotations from the file
	 * @return array
	 */
	public function getAnnotations()
	{
		$contents = $this->getContents();
		$lines = explode("\n", $contents);
		$annotations = array();
		
		foreach ($lines as $line) {
			$line = trim($line);
			$match = null;
		
			$result = preg_match('/^\-\-[ \t]*?@supra:([^\s]+)[ \t]*(.*)$/', $line, $match);

			if ($result) {
				$name = strtolower($match[1]);
				$value = trim($match[2]);
				$annotations[$name] = $value;
			}
		}

		return $annotations;
	}
}
