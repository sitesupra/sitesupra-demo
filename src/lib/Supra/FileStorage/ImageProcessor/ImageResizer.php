<?php

namespace Supra\FileStorage\ImageProcessor;

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
	}


	/**
	 * Set target width
	 *
	 * @param int $width
	 * @return ImageProcessor 
	 */
	public function setTargetWidth($width)
	{
		$this->targetWidth = $width;
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
		$this->targetHeight = $height;
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
			throw new Exception('Source image is not set');
		}
		if (empty($this->targetWidth)) {
			throw new Exception('Target width is not set');
		}
		if (empty($this->targetHeight)) {
			throw new Exception('Target height is not set');
		}
		if (empty($this->targetFilename)) {
			throw new Exception('Target (output) file is not set');
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

			// set default dimensions for image-to-image copy
			$sourceLeft = 0;
			$sourceTop = 0;
			$sourceWidth = $imageInfo['width'];
			$sourceHeight = $imageInfo['height'];
			$destWidth = $this->targetWidth;
			$destHeight = $this->targetHeight;
			
			// get ratios 
			$wRatio = $imageInfo['width'] / $this->targetWidth;
			$hRatio = $imageInfo['height'] / $this->targetHeight;
			$maxRatio = max($wRatio, $hRatio);
			$minRatio = min($wRatio, $hRatio);

			if ($this->cropMode && ($minRatio >= 1)) {
				// set source dimensions to center (with target aspect ratio)
				$sourceHeight = $this->targetHeight * $minRatio;
				$sourceTop = round(($imageInfo['height'] - $sourceHeight) / 2);
				$sourceHeight = round($sourceHeight);

				$sourceWidth = $this->targetWidth * $minRatio;
				$sourceLeft = round(($imageInfo['width'] - $sourceWidth) / 2);
				$sourceWidth = round($sourceWidth);
				
			} else {
				// set destination dimension (with original aspect ratio)
				$destWidth = round($imageInfo['width'] / $maxRatio);
				$destHeight = round($imageInfo['height'] / $maxRatio);
				
			}
			
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
}
