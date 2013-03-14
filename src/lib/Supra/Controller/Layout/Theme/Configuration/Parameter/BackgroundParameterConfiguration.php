<?php

namespace Supra\Controller\Layout\Theme\Configuration\Parameter;

use Supra\Controller\Layout\Theme\Configuration\ThemeParameterConfigurationAbstraction;
use Supra\Controller\Pages\Entity\Theme\Parameter\BackgroundParameter;

class BackgroundParameterConfiguration extends ThemeParameterConfigurationAbstraction
{
	
	public $iconStyle;
	
	public $htmlClassNameTarget;
	
	/**
	 * @return string
	 */
	protected function getParameterClass()
	{
		return BackgroundParameter::CN();
	}
	
	public function getEditorType()
	{
		return 'SelectVisual';
	}
		
	public function getAdditionalProperties()
	{
		return array(
			'iconStyle' => $this->iconStyle,
			'htmlClassNameTarget' => $this->htmlClassNameTarget,
		);
	}
}
