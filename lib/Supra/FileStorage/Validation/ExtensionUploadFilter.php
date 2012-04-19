<?php

namespace Supra\FileStorage\Validation;

use Supra\FileStorage\Exception;
use Supra\FileStorage\Entity\File;
use Supra\FileStorage\Helpers\BlackWhiteListCheck;

class ExtensionUploadFilter extends BlackWhiteListCheck implements FileValidationInterface
{
	const EXCEPTION_MESSAGE_KEY = 'medialibrary.validation_error.not_allowed_extension';
	
	/**
	 * Validates file extension
	 * @param File $file 
	 */
	public function validateFile(File $file)
	{
		$result = $this->checkList($file->getExtension());
		
		if( ! $result) {
			$message = 'File extension "'.$file->getExtension().'" is not allowed';
			\Log::info($message);
			
			throw new Exception\UploadFilterException(self::EXCEPTION_MESSAGE_KEY, $message);
		}
	}
	
}

