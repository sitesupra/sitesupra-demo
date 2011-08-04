<?php

namespace Supra\FileStorage\ImageProcessor;

use Supra\FileStorage\Exception\ImageProcessorException;

/**
 * Image resizer
 *
 */
class ImageResizer extends ImageProcessor
{
	/**
	 * Target width to fit image into
	 *
	 * @var int
	 */
	protected $targetWidth;

	/**
	 * Target height to fit image into
	 *
	 * @var int
	 */
	protected $targetHeight;
	
	/**
	 * Crop mode
	 *
	 * @var boolean
	 */
	private $cropMode = false;

	/**
	 * Set crop mode on/off
	 *
	 * @param boolean $value 
	 */
	public function setCropMode($value = false) 
	{
		$this->cropMode = (bool)$value;
		return $this;
	}


	/**
	 * Set target width
	 *
	 * @param int $width
	 * @return ImageProcessor 
	 */
	public function setTargetWidth($width)
	{
		$this->targetWidth = intval($width);
		return $this;
	}

	/**
	 * Set target height
	 *
	 * @param int $height
	 * @return ImageProcessor 
	 */
	public function setTargetHeight($height)
	{
		$this->targetHeight = intval($height);
		return $this;
	}
	
	/**
	 * Process
	 * 
	 */
	public function process()
	{

		// parameter check
		if (empty($this->sourceFilename)) {
			throw new ImageProcessorException('Source image is not set');
		}
		if (empty($this->targetFilename)) {
			throw new ImageProcessorException('Target (output) file is not set');
		}
		if (empty($this->targetWidth) || ($this->targetWidth <= 0)) {
			throw new ImageProcessorException('Target width is not set or is invalid');
		}
		if (empty($this->targetHeight) || ($this->targetHeight <= 0)) {
			throw new ImageProcessorException('Target height is not set or is invalid');
		}
		
		// get original image info
		$imageInfo = $this->getImageInfo($this->sourceFilename);

		// check if image is not smaller than target size
		$needsResize = false;
		if (($imageInfo['width'] > $this->targetWidth)
			|| ($imageInfo['height'] > $this->targetHeight)
		) {
			$needsResize = true;
		}
		
		if ($needsResize) {
			/* resize image */

			// open source
			$sourceImage = $this->createImageFromFile($this->sourceFilename);

			$dimensions = 
					$this->calculateDimensions($imageInfo['width'], $imageInfo['height']);
			extract($dimensions);
			
			// create image resource for new image
			$resizedImage = imagecreatetruecolor($destWidth, $destHeight);
			// check if transparecy requires special treatment
			if (($imageInfo['mime'] == 'image/png') 
				|| ($imageInfo['mime'] == 'image/png')
			) {
				$this->preserveTransparency($sourceImage, $resizedImage);
			}
			
			// copy and resize
			imagecopyresampled($resizedImage, $sourceImage, 
					0, 0, 
					$sourceLeft, $sourceTop,
					$destWidth, $destHeight,
					$sourceWidth, $sourceHeight);

			// save to file
			$this->saveImageToFile($resizedImage, $this->targetFilename, 
					$imageInfo['mime'], $this->targetQuality);

		} elseif ($this->sourceFilename != $this->targetFilename) {
			// copy original
			copy($this->sourceFilename, $this->targetFilename);
		}

	}

	/**
	 * Process
	 * 
	 */
	public function resize() 
	{
		$this->process();
	}

	/**
	 * Reset this instance
	 * 
	 */
	public function reset()
	{
		parent::reset();
		$this->targetWidth = null;
		$this->targetHeight = null;
		$this->cropMode = false;
	}

	/**
	 * Calculate all required dimensions and offsets
	 *
	 * @param int $originalWidth
	 * @param int $originalHeight
	 * @return array
	 */
	public function calculateDimensions($originalWidth, $originalHeight)
	{
		// check if target size is set and valid
		if (empty($this->targetWidth) || ($this->targetWidth <= 0)) {
			throw new ImageProcessorException('Target width is not set or is invalid');
		}
		if (empty($this->targetHeight) || ($this->targetHeight <= 0)) {
			throw new ImageProcessorException('Target height is not set or is invalid');
		}
		
		$dimensions = array();
		
		// set default dimensions for image-to-image copy
		$dimensions['sourceLeft'] = 0;
		$dimensions['sourceTop'] = 0;
		$dimensions['sourceWidth'] = $originalWidth;
		$dimensions['sourceHeight'] = $originalHeight;
		$dimensions['destWidth'] = $this->targetWidth;
		$dimensions['destHeight'] = $this->targetHeight;

		// get ratios 
		$wRatio = $originalWidth / $this->targetWidth;
		$hRatio = $originalHeight / $this->targetHeight;
		$maxRatio = max($wRatio, $hRatio);
		$minRatio = min($wRatio, $hRatio);

		if ($this->cropMode && ($minRatio >= 1)) {
			// set source dimensions to center (with target aspect ratio)
			$sourceHeight = $this->targetHeight * $minRatio;
			$dimensions['sourceTop'] = 
					round(($originalHeight - $sourceHeight) / 2);
			$dimensions['sourceHeight'] = round($sourceHeight);

			$sourceWidth = $this->targetWidth * $minRatio;
			$dimensions['sourceLeft'] = 
					round(($originalWidth - $sourceWidth) / 2);
			$dimensions['sourceWidth'] = round($sourceWidth);

		} else {
			// set destination dimension (with original aspect ratio)
			$dimensions['destWidth'] = round($originalWidth / $maxRatio);
			$dimensions['destHeight'] = round($originalHeight / $maxRatio);
		}

		return $dimensions;
	}

}
