<?php

namespace Supra\Controller\Pages\Twig;

use Supra\Controller\Pages\BlockController;
use Supra\Response\TwigResponse;
use Twig_Markup;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Locale\Locale;
use Supra\FileStorage\Entity\Image;
use Supra\Html\HtmlTag;

/**
 * Supra page controller twig helper
 */
class TwigSupraBlockGlobal
{
	/**
	 * @var BlockController
	 */
	protected $blockController;
	
	/**
	 * @param BlockController $blockController
	 */
	public function __construct(BlockController $blockController)
	{
		$this->blockController = $blockController;
	}
	
	/**
	 * Outputs block property
	 * @param string $name
	 * @return string
	 */
	public function property($name)
	{
		$value = $this->blockController->getPropertyValue($name);
		
		return $value;
	}

	/**
	 * @param string $imageId
	 * @param integer $width
	 * @param integer $height
	 * @param boolean $cropped
	 * @return HtmlTag
	 */
	public function imageHtmlTag($imageId, $width, $height, $cropped = false)
	{
		$img = new HtmlTag('img');
		
		$fileStorage = ObjectRepository::getFileStorage($this);
		$image = $fileStorage->getDoctrineEntityManager()
				->find(Image::CN(), $imageId);
		
		if ( ! $image instanceof Image) {
			return;
		}

		$exists = $fileStorage->fileExists($image);

		if ( ! $exists) {
			return;
		}
		
		$sizeName = $fileStorage->createResizedImage($image, $width, $height, $cropped);
		$imageSize = $image->getImageSize($sizeName);
		
		$webPath = $fileStorage->getWebPath($image, $sizeName);
		$img->setAttribute('src', $webPath);
		$img->setAttribute('width', $imageSize->getWidth());
		$img->setAttribute('height', $imageSize->getHeight());
		
		return $img;
	}

}
