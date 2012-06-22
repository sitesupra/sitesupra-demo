<?php

namespace Supra\Upgrade\Database;

use Supra\Upgrade\UpgradeFileRecursiveIteratorAbstraction;

/**
 * Filters out SQL files
 */
class SqlFileRecursiveIterator extends UpgradeFileRecursiveIteratorAbstraction
{

	/**
	 * @param string $path 
	 */
	public function __construct($path)
	{
		parent::__construct($path, SqlUpgradeFile::CN);
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
		$accept = preg_match('/\.sql$/i', $filename);

		return $accept;
	}

}
