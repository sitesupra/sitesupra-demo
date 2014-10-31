<?php

namespace Supra\Package\Cms\FileStorage\ImageProcessor\Adapter;

use Supra\Package\Cms\FileStorage\Exception\ImageProcessorException,
		Supra\Package\Cms\FileStorage\ImageProcessor\ImageProcessor;

/**
 * Wrapper for php gd2 library
 */
class Gd2Adapter extends ImageProcessorAdapterAbstract
{
	/**
	 * @inheritdoc
	 */
	public static function isAvailable()
	{
		if (extension_loaded('gd')) {
			return true;
		}
		
		return false;
	}

	/**
	 * @inheritdoc
	 */
	public function doRotate($sourceName, $targetName, $degrees)
	{
		$image = $this->createImageFromFile($sourceName);
		$info = $this->getImageInfo($sourceName);
		
		$rotatedImage = imagerotate($image, ($degrees * (-1)), 0);

		$width = $info->getWidth();
		$height = $info->getHeight();
		$mimeType = $info->getType();
		
		// Rotated PNG image size fastfix
		// will result low-quality png image
		if ($mimeType === IMAGETYPE_PNG) {
			
			$pngImage = $this->createOutputImage($height, $width, $mimeType);

			imagecopy($pngImage, $rotatedImage, 0, 0, 0, 0, $height, $width);			
			$rotatedImage = $pngImage;
			
			//imagedestroy($pngImage);
		}

		//imagedestroy($image);

		$this->saveImageToFile($rotatedImage, $targetName, $mimeType);
	}
	
	/**
	 * @inheritdoc
	 */
	public function doResize($sourceName, $targetName, $width, $height, array $sourceDimensions)
	{
		$info = $this->getImageInfo($sourceName);
		$mimeType = $info->getType();
	
		$image = $this->createImageFromFile($sourceName);
		$resizedImage = $this->createOutputImage($width, $height, $mimeType);
		
		imagecopyresampled($resizedImage, $image, 
					0, 0, 
					$sourceDimensions['left'], $sourceDimensions['top'],
					$width, $height,
					$sourceDimensions['width'], $sourceDimensions['height']);

		// save to file
		$this->saveImageToFile($resizedImage, $targetName, $mimeType);		
	}
	
	/**
	 * @inheritdoc
	 */
	public function doCrop($sourceName, $targetName, $width, $height, $x, $y)
	{
		$image = $this->createImageFromFile($sourceName);
		$info = $this->getImageInfo($sourceName);
		
		$mimeType = $info->getType();
		
		$croppedImage = $this->createOutputImage($width, $height, $mimeType);	
	
		imagecopy($croppedImage, $image, 0, 0, $x, $y, $width, $height);

		$this->saveImageToFile($croppedImage, $targetName, $mimeType);
		
	}
	
	/**
	 * @param integer $width
	 * @param integer $height
	 * @param integer $mimeType
	 * @return resource
	 */
	protected function createOutputImage($width, $height, $mimeType)
	{
		if ($mimeType === IMAGETYPE_PNG) {
			$image = imagecreate($width, $height);
		} else {
			$image = imagecreatetruecolor($width, $height);
		}
		
		return $image;
	}
	
	/**
	 * @param string $fileName
	 * @return resource
	 * @throws ImageProcessorException
	 */
	protected function createImageFromFile($fileName)
	{
		$info = $this->getImageInfo($fileName);
		
		$mimeType = $info->getType();
		$mimeName = $info->getMime();
		
		if ( ! ImageProcessor::isSupportedImageType($mimeType)) {
			throw new ImageProcessorException("{$mimeName} images are not supported");
		}

		switch ($mimeType) {

			case IMAGETYPE_GIF:
				$image = imageCreateFromGIF($fileName);
				break;
			case IMAGETYPE_JPEG:
				$image = imageCreateFromJPEG($fileName);
				break;
			case IMAGETYPE_PNG:
				
				$memoryLimit = ini_get('memory_limit');
				
				if ($memoryLimit != '-1') {
					
					sscanf($memoryLimit, '%dM', $memoryLimit);
					
					$memoryLimit = $memoryLimit * 1024 * 1024;
					
					$memoryUsed = memory_get_usage();
					
					$memoryFree = $memoryLimit - $memoryUsed;
					
					if ($memoryFree < $info->getWidth() * $info->getHeight() * $info->getBits()) {
						throw new ImageProcessorException("Failed to create image " . $info->getName() . " from {$mimeName} format, not enough memory.");
					}
				}
				
				$image = imageCreateFromPNG($fileName);
				break;
		}

		if ( ! is_resource($image)) {
			throw new ImageProcessorException("Failed to create image {$fileName} from {$mimeName} format.");
		}

		return $image;
	}
	
	/**
	 * @param resource $image
	 * @param string $fileName
	 * @param integer $mimeType
	 * @return string
	 * @throws ImageProcessorException
	 */
	protected function saveImageToFile($image, $fileName, $mimeType)
	{
		if ( ! ImageProcessor::isSupportedImageType($mimeType)) {
			throw new ImageProcessorException("Type {$mimeType} images are not supported");
		}

		switch ($mimeType) {
			case IMAGETYPE_GIF:
				return imagegif($image, $fileName);

			case IMAGETYPE_JPEG:
				return imagejpeg($image, $fileName, 90);

			case IMAGETYPE_PNG:
				return imagepng($image, $fileName, 0);
		}
	}
	
}