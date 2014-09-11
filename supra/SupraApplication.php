<?php

use Supra\Core\Supra;

class SupraApplication extends Supra
{
	protected function registerPackages()
	{
		return array(
			new \Supra\Package\Framework\SupraPackageFramework(),
			new \Supra\Package\Cms\SupraPackageCms(),
			new \Supra\Package\CmsAuthentication\SupraPackageCmsAuthentication(),
			new \Supra\Package\DebugBar\SupraPackageDebugBar()
		);
	}
}
