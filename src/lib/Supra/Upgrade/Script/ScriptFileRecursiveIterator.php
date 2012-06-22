<?php

namespace Supra\Upgrade\Script;

use Supra\Upgrade\UpgradeFileRecursiveIteratorAbstraction;

/**
 * Filters out script files
 */
class ScriptFileRecursiveIterator extends UpgradeFileRecursiveIteratorAbstraction
{

	/**
	 * @param string $path 
	 */
	public function __construct($path)
	{
		parent::__construct($path, ScriptUpgradeFile::CN);
	}

	/**
	 * Accepts filenames with SQL extension
	 * @return boolean
	 */
	public function accept()
	{
		$file = $this->current();
		/* @var $file \SplFileInfo */
		$filename = $file->getFilename();
		$accept = preg_match('/\.php$/i', $filename);

		return $accept;
	}

}
