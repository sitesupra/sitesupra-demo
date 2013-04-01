<?php

namespace Supra\Cms\MediaLibrary\Iconsidebar;

use Supra\Cms\MediaLibrary\MediaLibraryAbstractAction;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Layout\Theme\Configuration\ThemeIconSetConfiguration;

class IconsidebarAction extends MediaLibraryAbstractAction
{
	/**
	 * 
	 */
	public function loadAction()
	{
		$responseData = array();
		
		$theme = ObjectRepository::getThemeProvider($this)
				->getCurrentTheme();
		
		$configuration = $theme->getConfiguration();
		
		$iconConfiguration = $configuration->getIconConfiguration();
		
		if ($iconConfiguration instanceof ThemeIconSetConfiguration) {
			$responseData = array(
				'path' => $iconConfiguration->path,
				'icons' => $iconConfiguration->icons,
			);
		}
		
		$this->getResponse()
				->setResponseData($responseData);
	}

}