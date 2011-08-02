<?php

namespace Supra\FileStorage\Validation;
use Supra\FileStorage\Exception;

/**
 * File size validation class
 */
class FileSizeUploadFilter implements FileValidationInterface
{
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
	 * @param \Supra\FileStorage\Entity\File $file 
	 */
	public function validateFile(\Supra\FileStorage\Entity\File $file)
	{
		if ($file->getSize() > $this->maxSize) {
			$message = 'File size is bigger than "'.$this->maxSize;

			\Log::error($message);
			throw new Exception\UploadFilterException($message);
			
		}
	}

}
