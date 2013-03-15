<?php

namespace Supra\Controller\Layout\Theme\Configuration\Parameter;

use Supra\Controller\Layout\Theme\Configuration\ThemeConfigurationAbstraction;

class ParameterPresetGroupConfiguration extends ThemeConfigurationAbstraction
{
	/**
	 * @var string
	 */
	public $id;
	
	/**
	 * @var string
	 */
	public $label;
	
	/**
	 *
	 * @var string
	 */
	public $backgroundColor;
	
	/**
	 * @var array
	 */
	public $presets = array();
	
	/**
	 * 
	 */
	public function readConfiguration()
	{
		
	}

}
