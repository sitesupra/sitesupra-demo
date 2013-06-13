<?php

namespace Project\FancyBlocks\Text;

use Supra\Controller\Pages\BlockController;
//use Supra\Response;
//use Supra\Controller\Pages\Entity\ReferencedElement\ImageReferencedElement;
//use Supra\ObjectRepository\ObjectRepository;

/**
 * Simple text block
 */
class TextController extends BlockController
{

	public function doExecute()
	{
		$this->getResponse()
				->outputTemplate('index.html.twig');
	}
//
//	/**
//	 * @param string $name
//	 * @return mixed
//	 */
//	public function getPropertyValue($name)
//	{
//		if ($name == 'background_image') {
//			return $this->getBackgroundImagePropertyValue();
//		} else {
//			return parent::getPropertyValue($name);
//		}
//	}
//
//	protected function getBackgroundImagePropertyValue()
//	{
//		$property = $this->getProperty('background_image');
//		$editable = $property->getEditable();
//		$value = $editable->getFilteredValue();
//
//		$classname = null;
//		$imageData = null;
//
//		if ( ! empty($value)) {
//			$classname = $value;
//		} else if ($property->getMetadata()->containsKey('image')) {
//
//			/* @var $imageReferencedElement ImageReferencedElement */
//			$imageReferencedElement = $property->getMetadata()->get('image')->getReferencedElement();
//
//			$variantName = $imageReferencedElement->getSizeName();
//			$fileStorage = ObjectRepository::getFileStorage($this);
//
//			$image = $fileStorage->getDoctrineEntityManager()
//					->find(\Supra\FileStorage\Entity\Image::CN(), $imageReferencedElement->getImageId());
//			
//			$imageData = $fileStorage->getWebPath($image, $variantName);
//		}
//
//		$propertyValue = array('image' => $imageData, 'classname' => $classname);
//
//		return $propertyValue;
//	}

}
