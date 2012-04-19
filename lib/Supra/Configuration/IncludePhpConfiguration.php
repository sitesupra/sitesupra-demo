<?php

namespace Supra\Configuration;

/**
 * Includes additional PHP configuration
 */
class IncludePhpConfiguration extends IncludeConfiguration
{
	/**
	 * Just include file once. Will raise warning on file miss.
	 * @param string $file
	 */
	protected function parseFile($file)
	{
		include_once $file;
	}
}
