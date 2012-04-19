<?php

namespace Supra\FileStorage\Validation;

use Supra\FileStorage\Entity;
use Supra\FileStorage\Exception;

/**
 * File size validation class
 */
class FileSizeUploadFilter implements FileValidationInterface
{
	const EXCEPTION_MESSAGE_KEY = 'medialibrary.validation_error.file_size';
	
	/**
	 * Maximum file upload size
	 * @var integer $maxSize
	 */
	private $maxSize = 4096;
	
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
	public function validateFile(Entity\File $file)
	{
		$size = $file->getSize();
		
		if ($size > $this->maxSize) {
			$message = 'File size is bigger than "' . $this->maxSize . '"';
			\Log::info($message);
			
			throw new Exception\UploadFilterException(self::EXCEPTION_MESSAGE_KEY, $message);
		}
	}

}
