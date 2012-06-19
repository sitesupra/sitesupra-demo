<?php

namespace Supra\Controller\Pages\Twig;

use Supra\Controller\Pages\BlockController;
use Supra\Response\TwigResponse;
use Twig_Markup;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Locale\Locale;
use Supra\FileStorage\Entity\Image;
use Supra\FileStorage\Entity\ImageSize;
use Supra\Html\HtmlTag;
use Supra\Form\FormBlockController;

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
	 * @var BlockController
	 */
	protected $form;
	
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
		
		if ($blockController instanceof FormBlockController) {
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
	 * Mark image for preload
	 * @param string $imageId
	 * @param integer $width
	 * @param integer $height
	 * @param boolean $cropped
	 */
	public function preloadImage($imageId, $width = null, $height = null, $cropped = false)
	{
		$this->preloadImageData[$width][$height][$cropped][$imageId] = true;
	}
	
	/**
	 * Does the preload
	 * @param integer $width
	 * @param integer $height
	 * @param boolean $cropped
	 */
	private function doPreloadImages($width, $height, $cropped)
	{
		$fileStorage = ObjectRepository::getFileStorage($this);
		$em = $fileStorage->getDoctrineEntityManager();
		
		$imageIds = array_keys($this->preloadImageData[$width][$height][$cropped]);
		
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
		
		$sizes = $qb->getQuery()->getResult();
		
		foreach ($sizes as $key => $size) {
			$sizes[$size->getMaster()->getId()] = $size;
		}
		
		foreach ($images as $imageId => $image) {
			
			$imageSize = null;
			
			if (empty($sizes[$imageId])) {
				$sizeName = $fileStorage->createResizedImage($image, $width, $height, $cropped);
				$imageSize = $image->getImageSize($sizeName);
			} else {
				$imageSize = $sizes[$imageId];
			}
			
			$width = $imageSize->getWidth();
			$height = $imageSize->getHeight();
			
			$img = new HtmlTag('img');
			
			$webPath = $fileStorage->getWebPath($image, $imageSize);
			$img->setAttribute('src', $webPath);
			$img->setAttribute('width', $width);
			$img->setAttribute('height', $height);
			
			$this->preloadedImages[$width][$height][$cropped][$imageId] = $img;
		}
		
		// Clear data
		$this->preloadImageData[$width][$height][$cropped] = array();
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
		
		// For now..
		if (is_null($width)) {
			$width = 10000;
		}
		
		if (is_null($height)) {
			$height = 10000;
		}
		
		if (isset($this->preloadedImages[$width][$height][$cropped][$imageId])) {
			return $this->preloadedImages[$width][$height][$cropped][$imageId];
		}
		
		$this->preloadImage($imageId, $width, $height, $cropped);
		$this->doPreloadImages($width, $height, $cropped);
		
		return $this->preloadedImages[$width][$height][$cropped][$imageId];
	}
	
	public function getForm()
	{
		return $this->form;
	}

}
