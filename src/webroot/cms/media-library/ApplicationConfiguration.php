<?php

namespace Supra\Cms\MediaLibrary;

/**
 * ApplicationConfiguration
 */
class ApplicationConfiguration extends \Supra\Cms\ApplicationConfiguration
{
	const CHECK_FULL = 'full';
	const CHECK_PARTIAL = 'partial';
	const CHECK_NONE = 'none';
	
	/**
	 * @var string
	 */
	public $checkFileExistance = self::CHECK_NONE;
	
	/**
	 * @var array
	 */
	public $knownFileExtensions = array();
}