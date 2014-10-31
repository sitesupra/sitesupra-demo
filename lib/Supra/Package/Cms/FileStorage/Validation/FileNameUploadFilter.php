<?php

namespace Supra\Package\Cms\FileStorage\Validation;

use Supra\Package\Cms\Entity\Abstraction\File;
use Supra\Package\Cms\FileStorage\Exception;
use Supra\Package\Cms\FileStorage\Helpers;

/**
 * File and folder name validation class. Check folder and file name on forbidden characters
 */
class FileNameUploadFilter extends FileFolderSharedValidation
{
	const EXCEPTION_MESSAGE_KEY = 'medialibrary.validation_error.invalid_name';
	
	/**
	 * Validates filename for files and folders
	 * @param File $file
	 * @param string $typeName
	 */
	public function validate(File $file, $typeName, $sourceFilePath = null)
	{
		$name = $file->getFileName();
		$fileNameHelper = new Helpers\FileNameValidationHelper();
		$result = $fileNameHelper->validate($name);
		
		if( ! $result) {
			$message = $fileNameHelper->getErrorMessage();
			
			throw new Exception\UploadFilterException(self::EXCEPTION_MESSAGE_KEY, $message);
		}
	}
	
}