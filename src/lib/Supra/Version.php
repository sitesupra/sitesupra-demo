<?php

namespace Supra;

/**
 * Supra version
 */
class Version
{
	/**
     * Current Supra Version
     */
    const VERSION = '7.1.1-DEV';

    /**
     * Compares a version with the current one.
     *
     * @param string $version version to compare.
     * @return int returns -1 if older, 0 if it is the same, 1 if version passed
	 * as argument is newer.
     */
    public static function compare($version)
    {
        $currentVersion = str_replace(' ', '', strtolower(self::VERSION));
        $version = str_replace(' ', '', $version);

        return version_compare($version, $currentVersion);
    }
}
