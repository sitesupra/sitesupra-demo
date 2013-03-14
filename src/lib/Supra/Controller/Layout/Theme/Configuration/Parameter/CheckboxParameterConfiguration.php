<?php

namespace Supra\Controller\Layout\Theme\Configuration\Parameter;

use Supra\Controller\Layout\Theme\Configuration\ThemeParameterConfigurationAbstraction;
use Supra\Controller\Pages\Entity\Theme\Parameter\CheckboxParameter;

class CheckboxParameterConfiguration extends ThemeParameterConfigurationAbstraction
{
	
	/**
	 * @var array
	 */
	public $labels = array();
	
	protected function getParameterClass()
	{
		return CheckboxParameter::CN();
	}

	public function getEditorType()
	{
		return 'Checkbox';
	}
	
	public function getAdditionalProperties() 
	{
		return array(
			'labels' => $this->labels,
		);
	}

}
