<?php

namespace Supra\BannerMachine\Entity;

use Supra\FileStorage\Entity\Image as ImageFile;

/**
 * @Entity
 */
class ImageBanner extends FileBanner
{

	/**
	 * @return ImageFile
	 */
	public function getFile()
	{
		return $this->file;
	}

	/**
	 * @param ImageFile $file 
	 */
	public function setFile(ImageFile $file)
	{
		$this->file = $file;
	}

	/**
	 * @return string
	 */
	public function getContent()
	{
		return 'TROLOLO-IMAGE';
	}

	public function validate()
	{
		return true;
	}

}
