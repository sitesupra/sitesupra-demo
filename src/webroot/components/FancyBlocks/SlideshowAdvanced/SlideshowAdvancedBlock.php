<?php

namespace Project\FancyBlocks\SlideshowAdvanced;

use Supra\Controller\Pages\BlockController;
use	Supra\Controller\Pages\Entity\ReferencedElement\ImageReferencedElement;
use Supra\Editable\Slideshow;

class SlideshowAdvancedBlock extends BlockController
{
	const PROPERTY_SLIDES = 'slides';
	const DIRECTORY_SLIDESHOW_GENERATED_IMAGES = '__slide-generated';
	
	/**
	 * @var string
	 */
	private $generatedImagesFolderPath;
	
	/**
	 * @var string
	 */
	private $generatedImagesBaseUrl;
	
	/**
	 * @var ImageColorizer
	 */
	private $imageColorizer;
	
	/**
	 * 
	 */
	protected function doExecute()
	{
		$slides = $this->prepareSlides();
		
		$this->getResponse()
				->assign('slides', $slides)
				->outputTemplate('index.html.twig');
	}
	
	/**
	 * @return array
	 */
	protected function prepareSlides()
	{
		$slidesData = array();
		
		$slides = $this->getPropertyValue(self::PROPERTY_SLIDES);
		
		if ( ! empty($slides)) {
			
			$layouts = $this->getSlideLayouts();
			$now = time();
			
			foreach ($slides as $key => $slide) {
				
				$layoutId = $slide['layout'];
				// skip slides without valid layout file
				if ( ! isset($layouts[$layoutId])) {
        			continue;
        		}

				// skip slides, that are inactive
				if (isset($slide['active']) && $slide['active'] != 'true') {
					continue;
				}
				
				// skip slides, if it is expired or should be showed later
				$startDate = (isset($slide['period_from']) && ! empty($slide['period_from'])) ? strtotime($slide['period_from']) : null;
				$endDate = (isset($slide['period_to']) && ! empty($slide['period_to'])) ? strtotime($slide['period_to']) : null;
				
				if (($startDate && $now < $startDate) 
						|| ($endDate && $now > $endDate)) {
					
					continue;
				}
				
				$slide = $slide + array(
					'theme' => null,
					'mask_image' => null,
					'mask_color' => null,
					'text_main' => null,
					'text_top' => null,
					'media' => null,
					'mediaType' => null,
					'image' => null,
					'background' => null,
					'background_color' => null,
					'layout' => null,
					'buttons' => null,
					'height' => null,
				);
				
				// Background mask handling
				$maskImage = null;
				if ( ! empty($slide['theme']) && ! empty($slide['mask_color'])) {
					
					$hexColor = ltrim($slide['mask_color'], '#');
					
					$backgroundMasks = $this->getBackgroundMasks();
					
					// Slide "theme" property acts as possible mask id (there is themes which uses mask)
					$maskId = $slide['theme'];
					if (isset($backgroundMasks[$maskId])) {
						
						$maskSourceFile = $backgroundMasks[$maskId];
						
						$targetDirectory = $this->getGeneratedImagesFolderPath();
						$targetName = $this->getUniquePngMaskName($maskId, $hexColor);
						
						$targetFilePath = $targetDirectory . $targetName;
						
						if ( ! file_exists($targetName)) {
							\Log::info("Missing mask \"{$maskId}\" for color \"#{$hexColor}\", attempting to create");
						
							$colorizer = $this->getImageColorizer();
							$colorizer->colorizePngImage($maskSourceFile, $targetFilePath, $hexColor);
						}
											
						$maskImage = $this->getGeneratedImagesBaseUrl() . $targetName;
					}
				}

        		$slideData = array(
					'theme' => $slide['theme'],
					'mask_image' => $maskImage,
					'text_main' => $this->filterHtml($slide['text_main']),
					'text_top' => $this->filterHtml($slide['text_top']),
					'media' => $this->filterMedia($slide['media']),
					'mediaType' => $this->getMediaType($slide['media']),
					'image' => $slide['image'],
					'background' => $this->filterBackground($slide['background']),
					'background_color' => $slide['background_color'],
					'layout' => $slide['layout'],
					'buttons' => $this->filterButtons($slide['buttons']),
					'height' => $slide['height'],
					'layout_file' => $layouts[$layoutId],
				);
				
				$slidesData[] = $slideData;
			}	
		}
		
		return $slidesData;
	}
	
	/**
	 * @param array $htmlData
	 * @return string
	 */
	protected function filterHtml($htmlData)
	{
		$html = $htmlData['html'];
		$data = (isset($htmlData['data']) ? $htmlData['data'] : array());
		
		$elements = array();
		foreach ($data as $key => $referencedElementData) {
			
			$element = \Supra\Controller\Pages\Entity\ReferencedElement\ReferencedElementAbstract::fromArray($referencedElementData);
			
			if ($element instanceof ImageReferencedElement) {
				$this->getImageElementSizeName($element);
			}
			
			$elements[$key] = $element;
		}
			
		$filter = new \Supra\Controller\Pages\Filter\ParsedHtmlFilter();
		$filteredHtml = $filter->doFilter($html, $elements);
		
		return $filteredHtml;
	}
	
	/**
	 * @param array $buttonsData
	 * @return array
	 */
	protected function filterButtons($buttonsData)
	{
		if ( ! empty($buttonsData)) {
			foreach ($buttonsData as &$button) {
				if (is_array($button['link'])) {
					
					$linkElement = new \Supra\Controller\Pages\Entity\ReferencedElement\LinkReferencedElement;
					$linkElement->fillArray($button['link']);
					
					$button['target'] = $linkElement->getTarget();
					$button['link'] = $linkElement;
				}
			}
		}
		
		return $buttonsData;
	}
	
	/**
	 * @param array $backgroundData
	 * @return array|null
	 */
	protected function filterBackground($backgroundData)
	{		
		if ( ! empty($backgroundData) && isset($backgroundData['image'])) {
			
			$element = new ImageReferencedElement;
			$element->fillArray($backgroundData['image']);
			
			$sizeName = $this->getImageElementSizeName($element);
			
			$backgroundData['image']['size_name'] = $sizeName;
			
			return $backgroundData['image'];
		}
		
		return null;
	}
	
	protected function filterMedia($mediaData)
	{
		if (isset($mediaData['type']) && $mediaData['type'] == 'image') {
			
			$element = new ImageReferencedElement;
			$element->fillArray($mediaData);
			
			$sizeName = $this->getImageElementSizeName($element);
			
			$mediaData['size_name'] = $sizeName;
		}
		
		return $mediaData;
	}
	
	protected function getMediaType($mediaData)
    {
        if (isset($mediaData['type'])) {
            return 'type-' . $mediaData['type'];
        }
        
        return null;
    }
	
	/**
	 * @TODO: performance?
	 * @param \Project\FancyBlocks\SlideshowAdvanced\ImageReferencedElement $element
	 * @return string
	 */
	protected function getImageElementSizeName(ImageReferencedElement $element)
	{
		$imageId = $element->getImageId();
		$width = $element->getWidth();
		$height = $element->getHeight();

		$fileStorage = $this->getFileStorage();

		$fsEm = $fileStorage->getDoctrineEntityManager();
		$image = $fsEm->find(\Supra\FileStorage\Entity\Image::CN(), $imageId);

		if ( ! $image instanceof \Supra\FileStorage\Entity\Image) {
			\Log::warn("Image by ID $imageId was not found inside the file storage specified." .
					" Maybe another file storage must be configured for the image size creator listener?");

			return;
		}

		// No dimensions
		if ($width > 0 && $height > 0 || $element->isCropped()) {

			if ($element->isCropped()) {
				$sizeName = $fileStorage->createImageVariant($image, $width, $height, $element->getCropLeft(), $element->getCropTop(), $element->getCropWidth(), $element->getCropHeight());
			} else {
				$sizeName = $fileStorage->createResizedImage($image, $width, $height);
			}
			$element->setSizeName($sizeName);
		}
		
		return $sizeName;
	}
	
	private function getFileStorage()
	{
		if (is_null($this->fileStorage)) {
			$this->fileStorage = \Supra\ObjectRepository\ObjectRepository::getFileStorage($this);
		}
		
		return $this->fileStorage;
	}
	
	
//	private function getTemporaryDirectoryName()
//	{
//		$directoryName = SUPRA_TMP_PATH . 'cms-masks' . DIRECTORY_SEPARATOR;
//		if ( ! file_exists($directoryName)) {
//			if ( ! @mkdir($directoryName, SITESUPRA_FOLDER_PERMISSION_MODE)) {
//				throw new \RuntimeException('Failed to create temporary directory for png mask images');
//			}
//		}
//		
//		return $directoryName;
//	}

	/**
	 * @return \Supra\Editable\Slideshow
	 */
	private function getSlideshowPropertyEditable()
	{
		$propertyConfiguration = $this->getConfiguration()
				->getProperty(self::PROPERTY_SLIDES);
		
		if ($propertyConfiguration === null) {
			throw new \RuntimeException('No configuration found for Slideshow property');
		}
		
		if ( ! $propertyConfiguration->editableInstance instanceof Slideshow) {
			throw new \RuntimeException('Slideshow property must have Slideshow editable');
		}
		
		return $propertyConfiguration->editableInstance;
	}
	
	/**
	 * @return array
	 */
	protected function getSlideLayouts()
	{
		$layouts = $this->getSlideshowPropertyEditable()
				->getLayouts();

		$layoutsArray = array();
		
		$classPath = \Supra\Loader\Loader::getInstance()
				->findClassPath(self::CN());
		
		$currentDirectory = dirname($classPath);
		
		foreach ($layouts as $layout) {
			// strip part of absolute path
			$layoutsArray[$layout['id']] = str_replace($currentDirectory, '', $layout['fileName']);
		}
		
		return $layoutsArray;
	}
	
	private function getBackgroundMasks()
	{
		$editable = $this->getSlideshowPropertyEditable();
		$masks = $editable->getBackgroundMasks();
		
		return $masks;
	}
	
	private function getUniquePngMaskName($maskId, $color)
	{
		return md5( $this->getBlock()->getId() . $maskId . $color ) . '.png';
	}
	
	/**
	 * 
	 * @return string
	 * @throws \RuntimeException
	 */
	private function getGeneratedImagesFolderPath()
	{
		if ($this->generatedImagesFolderPath === null) {
			$storage = \Supra\ObjectRepository\ObjectRepository::getFileStorage($this);

			$path = $storage->getExternalPath() . self::DIRECTORY_SLIDESHOW_GENERATED_IMAGES;
			if ( ! file_exists($path)) {
				if ( ! @mkdir($path, SITESUPRA_FOLDER_PERMISSION_MODE)) {
					throw new \RuntimeException("Failed to create directory {$path} to store Slideshow masks");
				}
			}
			
			$this->generatedImagesFolderPath = $path . DIRECTORY_SEPARATOR;
			
		}
		
		return $this->generatedImagesFolderPath;
	}
	
	private function getGeneratedImagesBaseUrl()
	{
		if ($this->generatedImagesBaseUrl === null) {
			$storage = \Supra\ObjectRepository\ObjectRepository::getFileStorage($this);
			$this->generatedImagesBaseUrl = $storage->getExternalUrlBase() . '/'
					. self::DIRECTORY_SLIDESHOW_GENERATED_IMAGES . '/';
		}
		
		return $this->generatedImagesBaseUrl;
	}
	
	private function getImageColorizer()
	{
		if ($this->imageColorizer === null) {
			$this->imageColorizer = new \SupraSite\ImageEditor\ImageColorizer;
		}
		
		return $this->imageColorizer;
	}
}
