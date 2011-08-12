<?php

namespace Supra\FileStorage\ImageProcessor;

use Supra\FileStorage\Exception\ImageProcessorException;

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
	 * @return array
	 */
	public function getImageInfo($filename)
	{
		if( ! file_exists($filename)|| ! is_readable($filename)) {
			throw new ImageProcessorException('File ' . $filename . ' not found');
		}
		
		$imageInfo = getimagesize($filename);

		if (empty($imageInfo[0]) && empty($imageInfo[1])) {
			throw new ImageProcessorException('Could not get image size information');
		} else {		
			$imageInfo['height'] = &$imageInfo['1'];
			$imageInfo['width'] = &$imageInfo['0'];
		}
		
	    return $imageInfo;
	}

	/**
	 * Get image width helper
	 *
	 * @param string $filename
	 * @return int
	 */
	public function getImageWidth($filename)
	{
		$imageInfo = $this->getImageInfo($filename);
		if (is_array($imageInfo) && isset($imageInfo['width'])) {
			return $imageInfo['width'];
		}
	}

	/**
	 * Get image height helper
	 *
	 * @param string $filename
	 * @return int
	 */
	public function getImageHeight($filename)
	{
		$imageInfo = $this->getImageInfo($filename);
		if (is_array($imageInfo) && isset($imageInfo['height'])) {
			return $imageInfo['height'];
		}
	}

	/**
	 * Get image mime-type helper
	 *
	 * @param string $filename
	 * @return string
	 */
	public function getImageMime($filename)
	{
		$imageInfo = $this->getImageInfo($filename);
		if (is_array($imageInfo) && isset($imageInfo['mime'])) {
			return $imageInfo['mime'];
		}
	}

	/**
	 * Create GD resource image from file
	 * 
	 * @param string $file
	 * @return boolean
	 */
	protected function createImageFromFile($filename)
	{
		$image = null;
		
		try {
			
			$imageInfo = $this->getImageInfo($filename);
			if (empty($imageInfo)) {
				throw new ImageProcessorException('Could not retrieve image info');
			}
			
			switch ($imageInfo['mime']) {
				
        		case 'image/gif':
					if (imagetypes() & IMG_GIF) {
						$image = imageCreateFromGIF($filename) ;
					} else {
						throw new ImageProcessorException('GIF images are not supported');
					}
					break;
					
				case 'image/jpeg':
					if (imagetypes() & IMG_JPG) {
						$image = imageCreateFromJPEG($filename) ;
					} else {
						throw new ImageProcessorException('JPEG images are not supported');
					}
					break;
					
				case 'image/png':
					if (imagetypes() & IMG_PNG) {
						$image = imageCreateFromPNG($filename) ;
					} else {
						throw new ImageProcessorException('PNG images are not supported');
					}
					break;
					
				case 'image/wbmp':
					if (imagetypes() & IMG_WBMP) {
						$image = imageCreateFromWBMP($filename) ;
					} else {
						throw new ImageProcessorException('WBMP images are not supported');
					}
					break;
					
				default:
					throw new ImageProcessorException($imageInfo['mime'] . ' images are not supported');
					break;
        	}
	    
	    } catch(Exception $e) {
	    	$error = $e->getMessage();
	    	Log::info('Image Loading Error: ' . $error);
	    }
		
	    return $image;
	}

	/**
	 * Save gd image to file
	 *
	 * @param type $image
	 * @param type $filename
	 * @param type $mimeType
	 * @param type $jpegQuality
	 */
	protected function saveImageToFile($image, $filename, $mimeType, $jpegQuality = 100) 
	{
		switch ($mimeType) {
			case 'image/gif':
				if (imagetypes() & IMG_GIF) {
					return imagegif($image, $filename);
				} else {
					throw new ImageProcessorException('GIF images are not supported');
				}
				break;

			case 'image/jpeg':
				if (imagetypes() & IMG_JPG) {
					return imagejpeg($image, $filename, $this->evaluateQuality(100, $jpegQuality));
				} else {
					throw new ImageProcessorException('JPEG images are not supported');
				}
				break;

			case 'image/png':
				if (imagetypes() & IMG_PNG) {
					return imagepng($image, $filename, 9 - $this->evaluateQuality(9, $jpegQuality));
				} else {
					throw new ImageProcessorException('PNG images are not supported');
				}
				break;

			case 'image/wbmp':
				if (imagetypes() & IMG_WBMP) {
					return imagewbmp($image, $filename);
				} else {
					throw new ImageProcessorException('WBMP images are not supported');
				}
				break;

			default:
				throw new ImageProcessorException($this->originalImageInfo['mime'] . ' images are not supported');
				break;
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
	 */
	protected function preserveTransparency($sourceImage, $destImage)
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
		elseif ($this->originalImageInfo[2] == IMAGETYPE_PNG) {

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
