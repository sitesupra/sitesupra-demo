<?php

namespace Supra\Package\Cms\FileStorage\ImageProcessor;

use Supra\Package\Cms\FileStorage\Exception\ImageProcessorException;

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
	protected function doProcess()
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
		if (($imageInfo->getWidth() > $this->targetWidth)
			|| ($imageInfo->getHeight() > $this->targetHeight)
		) {
			$needsResize = true;
		}
		
		if ($needsResize) {
			
			$dimensions = 
					$this->calculateDimensions($imageInfo->getWidth(), $imageInfo->getHeight());

			// @TODO: maybe it's possible to avoid usage of this array? 
			// (now used at GD2 imagecopyresampled())
			$sourceDimensions = array(
				'left' => $dimensions['sourceLeft'],
				'top' => $dimensions['sourceTop'],
				'width' => $dimensions['sourceWidth'],
				'height' => $dimensions['sourceHeight'],
			);
			
			$destWidth = $dimensions['destWidth'];
			$destHeight = $dimensions['destHeight'];
						
			$this->adapter
					->doResize($this->sourceFilename, $this->targetFilename, $destWidth, $destHeight, $sourceDimensions);

		} elseif ($this->sourceFilename != $this->targetFilename) {
			// copy original
			copy($this->sourceFilename, $this->targetFilename);
		}

		@chmod($this->targetFilename, SITESUPRA_FILE_PERMISSION_MODE);
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
	protected function calculateDimensions($originalWidth, $originalHeight)
	{
		// check if target size is set and valid
		if (empty($this->targetWidth) || ($this->targetWidth <= 0)) {
			throw new ImageProcessorException('Target width is not set or is invalid');
		}
		if (empty($this->targetHeight) || ($this->targetHeight <= 0)) {
			throw new ImageProcessorException('Target height is not set or is invalid');
		}

		$newDimensions = $this->getExpectedSize(false);
		
		$dimensions = array();
		
		// set default dimensions for image-to-image copy
		$dimensions['sourceLeft'] = null;
		$dimensions['sourceTop'] = null;
		$dimensions['sourceWidth'] = null;
		$dimensions['sourceHeight'] = null;
		
		$dimensions['destWidth'] = max(round($newDimensions['width']), 1);
		$dimensions['destHeight'] = max(round($newDimensions['height']), 1);

		// get ratios 
		$wRatio = max($originalWidth / $newDimensions['width'], 1);
		$hRatio = max($originalHeight / $newDimensions['height'], 1);

		if ( ! $this->cropMode) {
			$wRatio = $hRatio = max($wRatio, $hRatio);
		} else {
			$wRatio = $hRatio = min($wRatio, $hRatio);
		}

		// set source dimensions to center (with target aspect ratio)
		$sourceHeight = $newDimensions['height'] * $hRatio;
		$dimensions['sourceTop'] =
				round(($originalHeight - $sourceHeight) / 2);
		$dimensions['sourceHeight'] = round($sourceHeight);

		$sourceWidth = $newDimensions['width'] * $wRatio;
		$dimensions['sourceLeft'] =
				round(($originalWidth - $sourceWidth) / 2);
		$dimensions['sourceWidth'] = round($sourceWidth);

		return $dimensions;
	}

	/**
	 * Get expected dimensions of processed image
	 * @return array
	 */
	public function getExpectedSize($round = true)
	{
		if (empty($this->sourceFilename)) {
			throw new ImageProcessorException('Source image is not set');
		}
		if (empty($this->targetWidth) || ($this->targetWidth <= 0)) {
			throw new ImageProcessorException('Target width is not set or is invalid');
		}
		if (empty($this->targetHeight) || ($this->targetHeight <= 0)) {
			throw new ImageProcessorException('Target height is not set or is invalid');
		}

		$imageInfo = $this->getImageInfo($this->sourceFilename);

		$wRatio = max($imageInfo->getWidth() / $this->targetWidth, 1);
		$hRatio = max($imageInfo->getHeight() / $this->targetHeight, 1);

		if ( ! $this->cropMode) {
			$wRatio = $hRatio = max($wRatio, $hRatio);
		}

		$dimensions = array(
			'width' => $imageInfo->getWidth() / $wRatio,
			'height' => $imageInfo->getHeight() / $hRatio,
		);

		if ($round) {
			$dimensions = array_map('round', $dimensions);
		}

		return $dimensions;
	}

}
