<?php

namespace Supra\BannerMachine\Entity;

/**
 * @Entity
 */
class FlashBanner extends FileBanner
{

	public function getContent()
	{
		return 'TROLOLO-FLASH';
	}

	public function validate()
	{
		return true;
	}

}
