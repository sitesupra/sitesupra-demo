<?php

namespace Supra\Validation;

/**
 * File size validation class
 */
class FileSizeUploadFilter implements \Supra\Validation\UploadFilter
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
	public function validate(\Supra\FileStorage\Entity\File $file)
	{
		if ($file->getSize() > $this->maxSize) {
			$message = 'File size is bigger than "'.$this->maxSize;
			throw new \Supra\Validation\UploadFilterException($message);
			\Log::error($message);
		}
	}

}
