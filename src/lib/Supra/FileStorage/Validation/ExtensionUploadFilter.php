<?php

namespace Supra\FileStorage\Validation;
use Supra\FileStorage\Exception;

class ExtensionUploadFilter extends BlackWhiteListCheck implements FileValidationInterface
{
	/**
	 * Validates file extension
	 * @param \Supra\FileStorage\Entity\File $file 
	 */
	public function validateFile(\Supra\FileStorage\Entity\File $file)
	{
		$result = $this->checkList($file->getExtension());
		if( ! $result) {
			$message = 'File extension "'.$file->getExtension().'" is not allowed';
			\Log::info($message);
			throw new Exception\UploadFilterException($message);
		}
	}
	
}

