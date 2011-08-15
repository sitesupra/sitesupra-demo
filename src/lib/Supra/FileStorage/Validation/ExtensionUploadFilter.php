<?php

namespace Supra\FileStorage\Validation;

use Supra\FileStorage\Exception;
use Supra\FileStorage\Entity\File;
use Supra\FileStorage\Helpers\BlackWhiteListCheck;

class ExtensionUploadFilter extends BlackWhiteListCheck implements FileValidationInterface
{
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
			
			throw new Exception\UploadFilterException($message);
		}
	}
	
}

