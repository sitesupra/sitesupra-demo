<?php

namespace Supra\Package\Cms\FileStorage\ImageProcessor;

use Supra\Package\Cms\FileStorage\Exception\ImageProcessorException;

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
	 * @param integer $count
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
	protected function doProcess()
	{
		// parameter check
		if (empty($this->sourceFilename)) {
			throw new ImageProcessorException('Source image is not set');
		}
		if (empty($this->targetFilename)) {
			throw new ImageProcessorException('Target file name is not set');
		}

		if ($this->rotationCount != 0) {

			$degrees = $this->rotationCount * 90;
			
			$this->adapter
					->doRotate($this->sourceFilename, $this->targetFilename, $degrees);
		} 
		elseif ($this->sourceFilename != $this->targetFilename) {
			copy($this->sourceFilename, $this->targetFilename);
		}

		chmod($this->targetFilename, 0655);
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
	 */
	public function reset()
	{
		parent::reset();
		$this->rotationCount = 1;
	}
}
