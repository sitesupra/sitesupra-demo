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
	 * @var FormExtension
	 */
	protected $form;

	/**
	 * @param BlockController $blockController
	 */
	public function __construct(BlockController $blockController)
	{
		$this->blockController = $blockController;
		
		if($blockController instanceof \Supra\Form\FormBlockController) {
			$this->form = new FormExtension($blockController);
		}
	}

	/**
	 * Outputs block property
	 * @param string $name
	 * @return string
	 */
	public function property($name)
	{
		if (empty($name)) {
			return;
		}
		
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
	public function imageHtmlTag($imageId, $width = null, $height = null, $cropped = false)
	{
		if (empty($imageId)) {
			return;
		}

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

		$sizeName = null;


		// Needs original version
		if ( ! is_null($width) || ! is_null($height)) {

			// calculating sizes if only image height or width provided
			$calculationError = false;

			if (is_null($width) ^ is_null($height)) {

				$originalWidth = $image->getWidth();
				$originalHeight = $image->getHeight();

				$sizes = array();

				if (is_null($width)) {
					$sizes = $fileStorage->calculateImageSizeFromHeight($originalWidth, $originalHeight, $height);
				} elseif (is_null($height)) {
					$sizes = $fileStorage->calculateImageSizeFromWidth($originalWidth, $originalHeight, $width);
				}

				if (is_null($sizes['height']) || is_null($sizes['width'])) {
					$calculationError = true;
				}
				
				$width = $sizes['width'];
				$height = $sizes['height'];
				
			}

			if ( ! $calculationError) {
				$sizeName = $fileStorage->createResizedImage($image, $width, $height, $cropped);
				$imageSize = $image->getImageSize($sizeName);
				$width = $imageSize->getWidth();
				$height = $imageSize->getHeight();
			} else {
				$width = $image->getWidth();
				$height = $image->getHeight();
			}
		} else {
			$width = $image->getWidth();
			$height = $image->getHeight();
		}

		$webPath = $fileStorage->getWebPath($image, $sizeName);
		$img->setAttribute('src', $webPath);
		$img->setAttribute('width', $width);
		$img->setAttribute('height', $height);

		return $img;
	}
	
	/**
	 * @return FormExtension
	 */
	public function getForm()
	{
		return $this->form;
	}
	
	public function getBlock()
	{
		return $this->blockController->getBlock();
	}

}
