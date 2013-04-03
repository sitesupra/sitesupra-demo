<?php

namespace Supra\Controller\Layout\Theme\Configuration;


class GroupAdditionalConfiguration extends ThemeConfigurationAbstraction
{
	/**
	 * @var string
	 */
	public $name;
	
	/**
	 * @var array
	 */
	public $layouts = array();
	
	public function readConfiguration() { }
}
