<?php

namespace Supra\Controller\Layout\Theme\Configuration\Parameter;

use Supra\Controller\Layout\Theme\Configuration\ThemeParameterConfigurationAbstraction;
use Supra\Controller\Pages\Entity\Theme\Parameter\ColorParameter;

class ColorParameterConfiguration extends ThemeParameterConfigurationAbstraction
{
	/**
	 * @var boolean
	 */
	public $allowUnset;

    /**
     * @var array
     */
    public $customization = array();
	
	protected function getParameterClass()
	{
		return ColorParameter::CN();
	}
	
	public function getEditorType()
	{
		return 'Color';
	}
	
	public function getAdditionalProperties() 
	{
		return array(
			'allowUnset' => $this->allowUnset,
            'customization' => $this->customization,
		);
	}

}
