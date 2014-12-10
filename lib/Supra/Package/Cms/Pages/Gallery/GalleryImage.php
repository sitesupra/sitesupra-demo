<?php

namespace Supra\Package\Cms\Pages\Gallery;

use Supra\Package\Cms\Entity\Image;
use Supra\Package\Cms\FileStorage\FileStorage;
use Supra\Package\Cms\Pages\Html\ImageTag;

class GalleryImage
{
	/**
	 * @var Image
	 */
	protected $image;

	/**
	 * @var string
	 */
	public $title;

	/**
	 * @var string
	 */
	public $description;

	/**
	 * @param Image $image
	 * @param FileStorage $fileStorage
	 */
	public function __construct(Image $image, FileStorage $fileStorage)
	{
		$this->image = $image;
		$this->fileStorage = $fileStorage;
	}

	public function getTag($width = null, $height = null, $crop = true)
	{
		$tag = new ImageTag($this->image, $this->fileStorage);

		if ($width) {
			$tag->setWidth($width);
		}

		if ($height) {
			$tag->setHeight($height);
		}

		$tag->setCrop($crop);

		return $tag;
	}

	public function __toString()
	{
		return (string) $this->getTag();
	}
}