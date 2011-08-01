<?php

namespace Supra\FileStorage\UploadFilter;

use Supra\FileStorage\Helpers;

/**
 * File and folder name validation class. Check folder and file name on forbidden characters
 */
class FileNameUploadFilter implements FileValidationInterface, FolderValidationInterface
{

	public function validateFile(\Supra\FileStorage\Entity\File $file)
	{
		
		$this->validate($file->getName());
	}

	public function validateFolder(\Supra\FileStorage\Entity\Folder $folder)
	{
		$this->validate($folder->getName());
	}
	
	private function validate($name)
	{
		$fileNameHelper = new Helpers\FileNameValidationHelper();
		$fileNameHelper->validate($name);
	}
	
}