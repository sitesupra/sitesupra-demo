<?php

namespace Supra\BannerMachine\Entity;

use Supra\BannerMachine\BannerMachineController;

/**
 * @Entity
 */
class FlashBanner extends FileBanner
{

	public function getExposureModeContent(BannerMachineController $controller)
	{
		return 'TROLOLO-FLASH-EXPOSE';
	}

	public function getEditModeContent(BannerMachineController $controller)
	{
		return 'TROLOLO-FLASH-EDIT';
	}

	public function validate()
	{
		return true;
	}

}
