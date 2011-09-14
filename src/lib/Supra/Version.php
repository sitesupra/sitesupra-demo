<?php

namespace Supra;

/**
 * Supra version
 *
 */
class Version 
{

	const 
		VERSION = '7.0.0';

	/**
	 * Compare with current version
	 *
	 * @param  string  $version
	 * @return integer
	 */
	public static function compare($version)
	{
		return version_compare($version, self::VERSION);
	}
	
}
