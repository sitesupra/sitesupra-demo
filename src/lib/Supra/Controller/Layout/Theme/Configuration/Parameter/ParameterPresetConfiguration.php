<?php

namespace Supra\Controller\Layout\Theme\Configuration\Parameter;

use Supra\Controller\Layout\Theme\Configuration\ThemeConfigurationAbstraction;

class ParameterPresetConfiguration extends ThemeConfigurationAbstraction
{
	/**
	 * @var string
	 */
	public $id;
	
	/**
	 * @var array
	 */
	public $colors = array();
	
	/**
	 * @var string
	 */
	public $icon;
	
	/**
	 * @var string
	 */
	public $backgroundColor;

	/**
	 * @var array
	 */
	public $customization = array();
	
	/**
	 * 
	 */
	public function readConfiguration()
	{
		if (empty($this->customization)) {
			throw new \RuntimeException("Check your ParameterPreset {$this->id} 
				configuration, seems it have no any parameter defined");
		}
	}
	
	/**
	 * @return array
	 */
	public function toArray()
	{
		return array(
			'id' => $this->id,
			'colors' => $this->colors,
			'icon' => $this->icon, // getIconWebPath?
			'backgroundColor' => $this->backgroundColor,
			'customization' => $this->customization,
		);
	}
	
	/**
	 * @return array
	 */
	public function getAdditionalProperties()
	{
		return array();
	}

}
