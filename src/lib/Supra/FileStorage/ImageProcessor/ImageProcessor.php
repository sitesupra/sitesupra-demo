<?php

namespace Supra\FileStorage\ImageProcessor;

use Supra\FileStorage\Exception\ImageProcessorException;
use Supra\FileStorage\ImageInfo;

/**
 * Abstract image processor class
 *
 */
abstract class ImageProcessor
{

	/**
	 * Source file name (path)
	 *
	 * @var string
	 */
	protected $sourceFilename;

	/**
	 * Output image compression quality (0-100, JPEG)
	 *
	 * @var int
	 */
	protected $targetQuality = 100;

	/**
	 * Output file name (path)
	 *
	 * @var string
	 */
	protected $targetFilename;

	/**
	 * Get full image info (dimesions, mime-type etc)
	 *
	 * @param string $filename
	 * @return ImageInfo
	 */
	public function getImageInfo($filename)
	{
		$info = new ImageInfo($filename);

		if ($info->hasError()) {
			throw new ImageProcessorException('File ' . $filename . ' not found or is not readable. ' . $info->getError());
		}

		return $info;
	}

	/**
	 * Get image width helper
	 *
	 * @param string $filename
	 * @return int
	 */
	public function getImageWidth($filename)
	{
		return $this->getImageInfo($filename)->getWidth();
	}

	/**
	 * Get image height helper
	 *
	 * @param string $filename
	 * @return int
	 */
	public function getImageHeight($filename)
	{
		return $this->getImageInfo($filename)->getHeight();
	}

	/**
	 * Get image mime-type helper
	 *
	 * @param string $filename
	 * @return string
	 */
	public function getImageMime($filename)
	{
		return $this->getImageInfo($filename)->getMime();
	}

	/**
	 * Create GD resource image from file
	 * 
	 * @param string $filename
	 * @return resource
	 * @throws ImageProcessorException
	 */
	protected function createImageFromFile($filename)
	{
		$image = null;

		$imageInfo = $this->getImageInfo($filename);
		$mimeType = $imageInfo->getType();
		$mimeName = $imageInfo->getMime();

		if ( ! self::isSupportedImageType($mimeType)) {
			throw new ImageProcessorException($mimeName . ' images are not supported');
		}

		switch ($mimeType) {

			case IMAGETYPE_GIF:
				$image = imageCreateFromGIF($filename);
				break;

			case IMAGETYPE_JPEG:
				$image = imageCreateFromJPEG($filename);
				break;

			case IMAGETYPE_PNG:
				$image = imageCreateFromPNG($filename);
				break;
		}

		if ( ! is_resource($image)) {
			$baseName = pathinfo($filename, PATHINFO_BASENAME);
			throw new ImageProcessorException("Failed to create image {$baseName} from {$mimeName} format.");
		}

		return $image;
	}

	/**
	 * @param integer $imageType One of IMAGETYPE_* constants
	 * @return boolean
	 */
	public static function isSupportedImageType($imageType)
	{
		switch ($imageType) {

			case IMAGETYPE_GIF:
				if (imagetypes() & IMG_GIF) {
					return true;
				}
				break;

			case IMAGETYPE_JPEG:
				if (imagetypes() & IMG_JPG) {
					return true;
				}
				break;

			case IMAGETYPE_PNG:
				if (imagetypes() & IMG_PNG) {
					return true;
				}
				break;

			default:
				return false;
				break;
		}

		return false;
	}

	/**
	 * Save gd image to file
	 *
	 * @param resource $image
	 * @param string $filename
	 * @param int $mimeType
	 * @param int $jpegQuality
	 * @param string $mimeName
	 */
	protected function saveImageToFile($image, $filename, $mimeType, $jpegQuality, $mimeName)
	{
		if ( ! self::isSupportedImageType($mimeType)) {
			throw new ImageProcessorException("$mimeName ($mimeType) images are not supported");
		}

		switch ($mimeType) {
			case IMAGETYPE_GIF:
				return imagegif($image, $filename);

			case IMAGETYPE_JPEG:
				return imagejpeg($image, $filename, $this->evaluateQuality(100, $jpegQuality));

			case IMAGETYPE_PNG:
				return imagepng($image, $filename, 9 - $this->evaluateQuality(9, $jpegQuality));
		}
	}

	/**
	 * Evaluate quality for image resampling
	 * 
	 * @param int $maxAllowed
	 * @param int $userSetting
	 */
	protected function evaluateQuality($maxAllowed, $userSetting)
	{
		$userSetting = intval($userSetting);

		if ($userSetting > 100) {
			$userSetting = 100;
		}

		if ($userSetting < 0) {
			$userSetting = 0;
		}
		$result = $userSetting * $maxAllowed / 100;

		return $result;
	}

	/**
	 * Set source image file
	 *
	 * @param string $filename
	 * @return ImageProcessor 
	 */
	public function setSourceFile($filename)
	{
		if ( ! file_exists($filename)) {
			throw new ImageProcessorException('Source image does not exist');
		}
		$this->sourceFilename = $filename;
// TODO decide if such functionality is needed
//		if (empty($this->targetFilename)) {
//			$this->targetFilename = $filename;
//		}
		return $this;
	}

	/**
	 * Set output compression quality (JPEG)
	 *
	 * @param int $quality
	 * @return ImageProcessor 
	 */
	public function setOutputQuality($quality)
	{
		$this->targetQuality = $quality;
		return $this;
	}

	/**
	 * Set target (output) filename
	 *
	 * @param string $filename
	 * @return ImageProcessor 
	 */
	public function setOutputFile($filename)
	{
		$this->targetFilename = $filename;
		return $this;
	}

	/**
	 * Reset this instance
	 * 
	 */
	public function reset()
	{
		$this->sourceFilename = null;
		$this->targetQuality = 100;
		$this->targetFilename = null;
	}

	/**
	 * Preserve image transparency between copy source and destination
	 * Taken from http://mediumexposure.com/smart-image-resizing-while-preserving-transparency-php-and-gd-library/
	 * 
	 * @param resource $sourceImage
	 * @param resource $destImage 
	 * @param integer $mimeType
	 */
	protected function preserveTransparency($sourceImage, $destImage, $mimeType)
	{
		$transparentIndex = imagecolortransparent($sourceImage);

		// If we have a specific transparent color
		if ($transparentIndex >= 0) {

			// Get the original image's transparent color's RGB values
			$transparentColor = imagecolorsforindex($sourceImage, $transparentIndex);

			// Allocate the same color in the new image resource
			$transparentIndex = imagecolorallocate($destImage, $transparentColor['red'], $transparentColor['green'], $transparentColor['blue']);

			// Completely fill the background of the new image with allocated color.
			imagefill($destImage, 0, 0, $transparentIndex);

			// Set the background color for new image to transparent
			imagecolortransparent($destImage, $transparentIndex);
		}
		// Always make a transparent background color for PNGs that don't have one allocated already
		elseif ($mimeType == IMAGETYPE_PNG) {

			// Turn off transparency blending (temporarily)
			imagealphablending($destImage, false);

			// Create a new transparent color for image
			$color = imagecolorallocatealpha($destImage, 0, 0, 0, 127);

			// Completely fill the background of the new image with allocated color.
			imagefill($destImage, 0, 0, $color);

			// Restore transparency blending
			imagesavealpha($destImage, true);
		}
	}

	/**
	 * Process
	 * 
	 */
	abstract public function process();
}
