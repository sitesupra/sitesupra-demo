<?php

namespace Supra\Controller\Pages\Twig;

use Supra\Controller\Pages\BlockController;
use Supra\ObjectRepository\ObjectRepository;
use Supra\FileStorage\Entity\Image;
use Supra\FileStorage\Entity\ImageSize;
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
	 * @var array
	 */
	private $preloadImageData = array();
	
	/**
	 * @var array
	 */
	private $preloadedImages = array();

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
		if (empty($name)) {
			return;
		}
		
		$value = $this->blockController->getPropertyValue($name);

		return $value;
	}
	
	/**
	 * Checks, wheiter block property have raw value (RAW - means value without CMS div wrappers)
	 * @param string $name
	 * @return boolean
	 */
	public function isEmpty($name)
	{
		if (empty($name)) {
			return;
		}
		
		$propertyValue = $this->blockController->getProperty($name)
				->getValue();
		
		return empty($propertyValue);
	}
	
	/**
	 * Mark image for preload
	 * @param string $imageId
	 * @param integer $width
	 * @param integer $height
	 * @param boolean $cropped
	 */
	public function preloadImage($imageId, $width = null, $height = null, $cropped = false, $fromSize = null)
	{
		$this->preloadImageData[round($width)][round($height)][$cropped][$fromSize][$imageId] = true;
	}
	
	/**
	 * Does the preload
	 * @param integer $width
	 * @param integer $height
	 * @param boolean $cropped
	 */
	private function doPreloadImages($width, $height, $cropped, $fromSize)
	{
		$width = round($width);
		$height = round($height);
		
		$fileStorage = ObjectRepository::getFileStorage($this);
		$em = $fileStorage->getDoctrineEntityManager();
		
		$imageIds = array_keys($this->preloadImageData[$width][$height][$cropped][$fromSize]);
		
		if (empty($imageIds)) {
			return;
		}
		
		// Find images
		$qb = $em->createQueryBuilder()
				->select('i')
				->from(Image::CN(), 'i', 'i.id')
				->andWhere('i.id IN (?0)')
				->setParameters(array($imageIds));
		$images = $qb->getQuery()->getResult();
		
		$qb = $em->createQueryBuilder()
				->select('s')
				->from(ImageSize::CN(), 's')
				->andWhere('s.master IN (?0) AND s.targetWidth = ?1 AND s.targetHeight = ?2 AND s.cropMode = ?3')
				->setParameters(array($imageIds, $width, $height, $cropped));
		
		if ( ! is_null($fromSize)) {
			$qb->andWhere('s.name = ?4')
					->setParameter(4, $fromSize);
		}
		
		$sizes = $qb->getQuery()->getResult();
		
		foreach ($sizes as $key => $size) {
			$sizes[$size->getMaster()->getId()] = $size;
		}
		
		foreach ($images as $imageId => $image) {
			
			$imageSize = null;
			
			if (empty($sizes[$imageId])) {
				if ( ! empty($fromSize)) {
					 $sourceImageSize = $image->findImageSize($fromSize);
					 if ( ! is_null($sourceImageSize)) {
						 $sizeName = $fileStorage->createCroppedImageVariant($sourceImageSize, $width, $height, $cropped);		
					 } 
					 $imageSize = $image->getImageSize($sizeName);
				} else {
					$sizeName = $fileStorage->createResizedImage($image, $width, $height, $cropped);
					$imageSize = $image->getImageSize($sizeName);
				}
			} else {
				$imageSize = $sizes[$imageId];
			}
			
			$realWidth = $imageSize->getWidth();
			$realHeight = $imageSize->getHeight();
			
			$img = new HtmlTag('img');
			
			$webPath = $fileStorage->getWebPath($image, $imageSize);
						
			$img->setAttribute('src', $webPath);
			$img->setAttribute('width', $realWidth);
			$img->setAttribute('height', $realHeight);
			
			$this->preloadedImages[$width][$height][$cropped][$fromSize][$imageId] = $img;
		}
		
		// Clear data
		$this->preloadImageData[$width][$height][$cropped][$fromSize] = array();
	}
	
	public function imageHtmlTag($imageId, $width = null, $height = null, $cropped = false, $fromSize = null)
	{
		if (empty($imageId)) {
			return;
		}
		
		// For now...
		$width = ( ! is_null($width) ? round($width) : 10000);
		$height = ( ! is_null($height) ? round($height) : 10000);
		
		if (isset($this->preloadedImages[$width][$height][$cropped][$fromSize][$imageId])) {
			$tag = $this->preloadedImages[$width][$height][$cropped][$fromSize][$imageId];
			
			return $tag;
		}
		
		$this->preloadImage($imageId, $width, $height, $cropped, $fromSize);
		$this->doPreloadImages($width, $height, $cropped, $fromSize);
		
		$tag = $this->preloadedImages[$width][$height][$cropped][$fromSize][$imageId];
		
		return $tag;
	}
	
	/**
	 * @return \Supra\Controller\Pages\Entity\Abstraction\Block
	 */
	public function getBlock()
	{
		return $this->blockController->getBlock();
	}

}
