<?php

namespace Supra\Package\Cms\FileStorage\Validation;

use Supra\Package\Cms\Entity;
use Supra\Package\Cms\FileStorage\Exception;
use Supra\Package\Cms\FileStorage\ImageInfo;

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

		$info = new ImageInfo($sourceFilePath);
		if($info->hasError()) {
			throw new Exception\RuntimeException($info->getError());
		}

		// Assumes memory_limit is in MB
		// "-1" means no limit
		$memoryLimit = (int) ini_get('memory_limit');
		if ($memoryLimit < 0) {
			return;
		}
		
		$memoryLeft = (int) $memoryLimit * 1024 * 1024 - memory_get_usage(); // Should use real usage or not?
		// Read data from image info, default bitsPerChannel to 8, channel count to 4
		$memoryRequired = ($info->getWidth() * $info->getHeight() * ($info->getBits() ?: 8) * ($info->getChannels() ?: 4)) * 2;

		if ($memoryRequired > $memoryLeft) {
			$message = "Not enough memory [{$memoryLeft} bytes] to resize the image, required amount [{$memoryRequired} bytes]";

			throw new Exception\InsufficientSystemResources(self::EXCEPTION_MESSAGE_KEY, $message);
		}
	}

}
