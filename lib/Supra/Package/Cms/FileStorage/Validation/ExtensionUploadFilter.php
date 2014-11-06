<?php

namespace Supra\Package\Cms\FileStorage\Validation;

use Supra\Package\Cms\FileStorage\Exception;
use Supra\Package\Cms\Entity\File;
use Supra\Package\Cms\FileStorage\Helpers\BlackWhiteListCheck;

class ExtensionUploadFilter extends BlackWhiteListCheck implements FileValidationInterface
{
	const EXCEPTION_MESSAGE_KEY = 'medialibrary.validation_error.not_allowed_extension';
	
	/**
	 * Validates file extension
	 * @param File $file 
	 */
	public function validateFile(File $file, $sourceFilePath = null)
	{
		$result = $this->checkList($file->getExtension());
		
		if( ! $result) {
			$message = 'File extension "'.$file->getExtension().'" is not allowed';
			
			throw new Exception\UploadFilterException(self::EXCEPTION_MESSAGE_KEY, $message);
		}
	}
	
}

