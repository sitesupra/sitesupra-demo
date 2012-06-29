<?php

namespace Supra\FileStorage\ImageProcessor;

use Supra\FileStorage\Exception\ImageProcessorException;

/**
 * Image rotator
 *
 */
class ImageRotator extends ImageProcessor
{

	const
		ROTATE_RIGHT = 1,
		ROTATE_180 = 2,
		ROTATE_LEFT = 3;
	
	protected $rotationCount = 1;

	/**
	 * Set right angle rotation count. Negative is CCW
	 *
	 * @param type $count
	 * @return ImageRotator 
	 */
	public function setRotationCount($count) 
	{
		$count = intval($count);
		if ($count != 0) {
			$count = $count % 4;
			$this->rotationCount = $count;
		}
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

		if ($this->rotationCount != 0) {
			$imageInfo = $this->getImageInfo($this->sourceFilename);
			$image = $this->createImageFromFile($this->sourceFilename);

			$angle = $this->rotationCount * -90;
			$bgd_color = 0;
			$rotatedImage = imagerotate($image, $angle, $bgd_color);

			$this->saveImageToFile($rotatedImage, $this->targetFilename, 
					$imageInfo->getType(), $this->targetQuality, $imageInfo->getMime());
		} elseif ($this->sourceFilename != $this->targetFilename) {
			copy($this->sourceFilename, $this->targetFilename);
		}
	}

	/**
	 * Rotate
	 * 
	 */
	public function rotate()
	{
		$this->process();
	}

	/**
	 * Rotate right (set 90 degrees CW and process)
	 * 
	 */
	public function rotateRight()
	{
		$this->setRotationCount(self::ROTATE_RIGHT);
		$this->process();
	}

	/**
	 * Rotate left (set 90 degrees CCW and process)
	 * 
	 */
	public function rotateLeft()
	{
		$this->setRotationCount(self::ROTATE_LEFT);
		$this->process();
	}

	/**
	 * Rotate by 180 degrees (set 180 degrees and rotate)
	 */
	public function rotate180()
	{
		$this->setRotationCount(self::ROTATE_180);
		$this->process();
	}

	/**
	 * Reset this instance
	 * 
	 */
	public function reset()
	{
		parent::reset();
		$this->rotationCount = 1;
	}
}
