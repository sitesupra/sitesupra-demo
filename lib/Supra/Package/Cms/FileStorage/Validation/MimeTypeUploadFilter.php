<?php

namespace Supra\Package\Cms\FileStorage\Validation;

use Supra\Package\Cms\Entity\File;
use Supra\Package\Cms\FileStorage\Exception;

class MimeTypeUploadFilter implements FileValidationInterface
{
	const EXCEPTION_MESSAGE_KEY = 'medialibrary.validation_error.not_allowed_mime';
	
	/**
	 * Validates file mimetype
	 * @param File $file 
	 */
	public function validateFile(File $file, $sourceFilePath = null)
	{
		$result = $this->checkList($file->getMimeType());
		
		if( ! $result) {
			$message = 'File mimetype "'.$file->getMimeType().'" is not allowed';
			
			throw new Exception\UploadFilterException(self::EXCEPTION_MESSAGE_KEY, $message);
		}
	}

}
