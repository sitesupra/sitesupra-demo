<?php

namespace Supra\Request;

/**
 * Uploaded file data wrapper object
 */
class UploadedFile
{

	protected $name;

	protected $temporaryName;
	
	protected $uploadError;
	
	protected $mimeType;
	
	protected $size;

	
	public function __construct($fileData)
	{
		$this->name = $fileData['name'];
		$this->temporaryName = $fileData['tmp_name'];
		$this->uploadError = $fileData['error'];
		$this->mimeType = $fileData['type'];
		$this->size = $fileData['size'];
	}
	
	/**
	 * Filename of temporary file
	 * @return string
	 */
	public function getTemporaryName()
	{
		return $this->temporaryName;
	}
	
	/**
	 * Uploaded file name
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}
	
	/**
	 * @return string
	 */
	public function getUploadError()
	{
		return $this->uploadError;
	}
	
	/**
	 * Get uploaded file mime type
	 * NB! This value is untrusted!
	 */
	public function getMimeType()
	{
		return $this->mimeType;
	}
	
	/**
	 * Filesize in bytes
	 * 
	 * @return int
	 */
	public function getSize()
	{
		return $this->size;
	}
}
