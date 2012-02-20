<?php

namespace Supra\Database\Upgrade;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use FilterIterator;
use FilesystemIterator;

/**
 * Filters out SQL files
 */
class SqlFileRecursiveIterator extends FilterIterator
{
	private static $directoryIteratorFlags = array(
		FilesystemIterator::KEY_AS_PATHNAME,
		FilesystemIterator::CURRENT_AS_FILEINFO,
		FilesystemIterator::SKIP_DOTS,
		FilesystemIterator::UNIX_PATHS
	);

	public function __construct($path)
	{
		$flags = 0;
		foreach (self::$directoryIteratorFlags as $flag) {
			$flags |= $flag;
		}

		$directoryIterator = new RecursiveDirectoryIterator($path, $flags);
		$directoryIterator->setInfoClass(SqlUpgradeFile::CN);
		$recursiveIterator = new RecursiveIteratorIterator($directoryIterator, RecursiveIteratorIterator::LEAVES_ONLY);

		parent::__construct($recursiveIterator);
	}

	/**
	 * Accepts filenames with SQL extension
	 * @return boolean
	 */
	public function accept()
	{
		$filename = $this->current()->getFilename();
        $accept = preg_match('/\.sql$/i', $filename);

		return $accept;
    }
}
