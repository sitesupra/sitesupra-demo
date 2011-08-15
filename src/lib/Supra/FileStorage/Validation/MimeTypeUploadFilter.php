<?php

namespace Supra\FileStorage\Validation;

use Supra\FileStorage\Entity\File;
use Supra\FileStorage\Exception;

class MimeTypeUploadFilter implements FileValidationInterface
{
	/**
	 * Validates file mimetype
	 * @param File $file 
	 */
	public function validateFile(File $file)
	{
		$result = $this->checkList($file->getMimeType());
		
		if( ! $result) {
			$message = 'File mimetype "'.$file->getMimeType().'" is not allowed';
			\Log::info($message);
			
			throw new Exception\UploadFilterException($message);
		}
	}

}
