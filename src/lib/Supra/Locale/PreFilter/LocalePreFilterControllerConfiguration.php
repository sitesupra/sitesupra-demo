<?php

namespace Supra\Locale\PreFilter;

use Supra\Controller\Configuration\ControllerConfiguration;
use Supra\ObjectRepository\ObjectRepository;

/**
 * Locale PreFilter controller configuration class
 */
class LocalePreFilterControllerConfiguration extends ControllerConfiguration
{
	public $detectInactiveLocales;
	
	public function configure()
	{
		parent::configure();
		
		if ($this->detectInactiveLocales) {
			$localeManager = ObjectRepository::getLocaleManager('Supra\Cms\CmsController');
			$localeManager->processInactiveLocales();
		}
	}
}
