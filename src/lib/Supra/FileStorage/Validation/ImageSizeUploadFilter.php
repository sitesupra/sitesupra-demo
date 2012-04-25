<?php

namespace Supra\FileStorage\Validation;

use Supra\FileStorage\Entity;
use Supra\FileStorage\Exception;

/**
 * File size validation class
 */
class ImageSizeUploadFilter implements FileValidationInterface
{
	const EXCEPTION_MESSAGE_KEY = 'medialibrary.validation_error.image_file_size';
	
	/**
	 * Set max allowed file size in bytes
	 * @param integer $maxSize 
	 */
	public function setMaxSize($maxSize)
	{
		$this->maxSize = $maxSize;
	}

	/**
	 * Validates file size
	 * @param Entity\File $file 
	 */
	public function validateFile(Entity\File $file, $sourceFilePath = null)
	{
		if ( ! $file instanceof Entity\Image) {
			return;
		}
		
		if (is_null($sourceFilePath)) {
			return;
		}
		
		$imageSize = getimagesize($sourceFilePath);
		
		if (is_array($imageSize)) {
			// Assumes memory_limit is in MB
			$memoryLeft = (int) ini_get('memory_limit') * 1024 * 1024 - memory_get_usage(); // Should use real usage or not?
			
			// Read data from getimagesize, default bitsPerChannel to 8, channel count to 4
			$memoryRequired = $imageSize[0] * $imageSize[1] * $imageSize['bits'] * $imageSize['channels'];

			if (2 * $memoryRequired > $memoryLeft) {
				$message = "Not enough memory [{$memoryLeft} bytes] to resize the image, required amount [{$memoryRequired} bytes]";
				\Log::info($message);			
			
				throw new Exception\InsufficientSystemResources(self::EXCEPTION_MESSAGE_KEY, $message);
			}
		}
	}

}
