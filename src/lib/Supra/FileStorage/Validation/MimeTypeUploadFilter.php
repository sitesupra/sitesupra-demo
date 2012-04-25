<?php

namespace Supra\FileStorage\Validation;

use Supra\FileStorage\Entity\File;
use Supra\FileStorage\Exception;

class MimeTypeUploadFilter implements FileValidationInterface
{
	const EXCEPTION_MESSAGE_KEY = 'medialibrary.validation_error.not_allowed_mime';
	
	/**
	 * Validates file mimetype
	 * @param File $file 
	 */
	public function validateFile(File $file, $sourceFilePath)
	{
		$result = $this->checkList($file->getMimeType());
		
		if( ! $result) {
			$message = 'File mimetype "'.$file->getMimeType().'" is not allowed';
			\Log::info($message);
			
			throw new Exception\UploadFilterException(self::EXCEPTION_MESSAGE_KEY, $message);
		}
	}

}
