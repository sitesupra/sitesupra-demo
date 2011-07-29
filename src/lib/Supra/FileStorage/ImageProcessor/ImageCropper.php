<?php

namespace Supra\FileStorage\ImageProcessor;

/**
 * Image cropper
 *
 */
class ImageCropper extends ImageProcessor
{

	protected $left = 0;
	protected $top = 0;
	protected $right = 0;
	protected $bottom = 0;
	protected $width = 0;
	protected $height = 0;

	/**
	 * Set left offset
	 *
	 * @param int $value
	 * @return ImageCropper 
	 */
	public function setLeft($value)
	{
		$this->left = intval($value);
		return $this;
	}

	/**
	 * Set top offset
	 *
	 * @param int $value
	 * @return ImageCropper 
	 */
	public function setTop($value)
	{
		$this->top = intval($value);
		return $this;
	}

	/**
	 * Set right offset (positive from left, negative from right). Will override 
	 * crop width
	 *
	 * @param int $value
	 * @return ImageCropper 
	 */
	public function setRight($value)
	{
		$this->right = intval($value);
		if ($this->right != 0) {
			$this->width = 0;
		}
		return $this;
	}

	/**
	 * Set bottom offset (positive from top, negative from bottom). Will 
	 * override crop height
	 *
	 * @param int $value
	 * @return ImageCropper 
	 */
	public function setBottom($value)
	{
		$this->bottom = intval($value);
		if ($this->bottom != 0) {
			$this->height = 0;
		}
		return $this;
	}

	/**
	 * Set crop width. Will override right offset
	 *
	 * @param int $value
	 * @return ImageCropper 
	 */
	public function setWidth($value)
	{
		$this->width = intval($value);
		if ($this->width != 0) {
			$this->right = 0;
		}
		return $this;
	}

	/**
	 * Set crop height. Will override bottom offset
	 *
	 * @param int $value
	 * @return ImageCropper 
	 */
	public function setHeight($value)
	{
		$this->height = intval($value);
		if ($this->height != 0) {
			$this->bottom = 0;
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
			throw new Exception('Source image is not set');
		}
		if (empty($this->targetFilename)) {
			throw new Exception('Target (output) file is not set');
		}

		// check if there are enough dimensions
		if (($this->left == 0) 
			|| ($this->top == 0) 
			|| (($this->right == 0) && ($this->width == 0))
			|| (($this->bottom == 0) && ($this->height == 0))
		) {
			throw new Exception('Crop dimensions are incomplete to process');
		}

		$image = $this->createImageFromFile($this->sourceFilename);
		$imageInfo = $this->getImageInfo($this->sourceFilename);

		// check if left and top are in range
		if (($this->left < 0) || ($this->left >= $imageInfo['width'])) {
			throw new Exception('Left offset is out of borders');
		}
		if (($this->top < 0 ) || ($this->top >= $imageInfo['height'])) {
			throw new Exception('Top offset is out of borders');
		}

		// invert right and bottom if required
		if ($this->right < 0) {
			$this->right = $imageInfo['width'] + $this->right - 1;
		}
		if ($this->bottom < 0) {
			$this->bottom = $imageInfo['height'] + $this->bottom - 1;
		}

		// check if right and bottom are in range
		if ($this->right >= $imageInfo['width']) {
			throw new Exception('Right offset is out of borders');
		}
		if ($this->bottom >= $imageInfo['height']) {
			throw new Exception('Bottom offset is out borders');
		}

		//convert right/bottom to width/height or check width/height
		if ($this->right != 0) {
			$this->width = $this->right - $this->left + 1;
		} else if ($this->width >= ($imageInfo['width'] - $this->left)) {
			throw new Exception('Crop width exceeds maximum (out of borders)');
		}
		if ($this->bottom != 0) {
			$this->height = $this->bottom - $this->top + 1;
		} else if ($this->height >= ($imageInfo['height'] - $this->top)) {
			throw new Exception('Crop height exceeds maximum (out of borders)');
		}

		// create output image
		$croppedImage = imagecreatetruecolor($this->width, $this->height);
		
		// check if transparecy requires special treatment
		if (($imageInfo['mime'] == 'image/png') 
			|| ($imageInfo['mime'] == 'image/png')
		) {
			$this->preserveTransparency($image, $croppedImage);
		}

		//copy cropped
		imagecopy($croppedImage, $image, 
				0, 0, 
				$this->left, $this->top, 
				$this->width, $this->height);

		// save to file
		$this->saveImageToFile($croppedImage, $this->targetFilename, 
				$imageInfo['mime'], $this->targetQuality);
		
	}

	/**
	 * Process
	 * 
	 */
	public function crop()
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
		$this->left = 0;
		$this->top = 0;
		$this->right = 0;
		$this->bottom = 0;
		$this->width = 0;
		$this->height = 0;
	}
}
