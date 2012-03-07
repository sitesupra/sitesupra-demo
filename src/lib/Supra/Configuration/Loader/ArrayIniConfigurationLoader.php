<?php

namespace Supra\Configuration\Loader;

/**
 * Array source ini configuration loader
 */
class ArrayIniConfigurationLoader extends IniConfigurationLoader
{
	public function __construct(array $data)
	{
		$this->filename = '[Array]';
		$this->data = $data;
	}
}
