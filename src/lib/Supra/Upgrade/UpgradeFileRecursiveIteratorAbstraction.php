<?php

namespace Supra\Upgrade;

use \RecursiveDirectoryIterator;
use \RecursiveIteratorIterator;
use \FilterIterator;
use \FilesystemIterator;

/**
 * Filters out upgrade files
 */
abstract class UpgradeFileRecursiveIteratorAbstraction extends FilterIterator
{

	private static $directoryIteratorFlags = array(
		FilesystemIterator::KEY_AS_PATHNAME,
		FilesystemIterator::CURRENT_AS_FILEINFO,
		FilesystemIterator::SKIP_DOTS,
		FilesystemIterator::UNIX_PATHS
	);

	public function __construct($path, $infoClassName)
	{
		$flags = 0;
		foreach (self::$directoryIteratorFlags as $flag) {
			$flags |= $flag;
		}

		$directoryIterator = new RecursiveDirectoryIterator($path, $flags);
		$directoryIterator->setInfoClass($infoClassName);
		$recursiveIterator = new RecursiveIteratorIterator($directoryIterator, RecursiveIteratorIterator::LEAVES_ONLY);

		parent::__construct($recursiveIterator);
	}

}
