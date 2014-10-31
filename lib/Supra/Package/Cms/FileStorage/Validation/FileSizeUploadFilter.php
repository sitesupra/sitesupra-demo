<?php

namespace Supra\Package\Cms\FileStorage\Validation;

use Supra\Package\Cms\Entity;
use Supra\Package\Cms\FileStorage\Exception;

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
	public function validateFile(Entity\File $file, $sourceFilePath = null)
	{
		$size = $file->getSize();
		
		if ($size > $this->maxSize) {
			$message = 'File size is bigger than "' . $this->maxSize . '"';
			
			throw new Exception\UploadFilterException(self::EXCEPTION_MESSAGE_KEY, $message);
		}
	}

}
