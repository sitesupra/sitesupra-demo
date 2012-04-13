<?php

namespace Supra\Validator\Type;

use Supra\Validator\Exception;
use Supra\FileStorage\Exception\FileUploadException;

/**
 * Post uploaded file validation type
 */
class PostFileType extends AbstractType
{
	
	public function validate(&$value, $additionalParams = null)
	{
		
		if ( ! isset($value['error'])) {
			throw new Exception\ValidationFailure('File have no correct upload data');	
		}
		
		if ($value['error'] != UPLOAD_ERR_OK) {
			throw new Exception\ValidationFailure('Failed to upload file', null, FileUploadException::fire($value['error']));
		}
		
		if ( ! is_readable($value['tmp_name'])) {
			throw new Exception\ValidationFailure('File is not accessible');
		}
		
		if ( ! empty($additionalParams)) {
			
			if (isset($additionalParams['types'])) {
				$mimeType = $this->detectMimeType($value['tmp_name']);
		
				if ( ! in_array($mimeType, $additionalParams['types'])) {
					throw new Exception\ValidationFailure('Uploaded file type is not valid');
				}
			}
			if (isset($additionalParams['size'])) {
				if ($value['size'] > $additionalParams['size']) {
					throw new Exception\ValidationFailure('Uploaded file size exceeding specified one');
				}
			}
		}
	}
	
	/**
	 * Tries to detect file's MIME type using PECL Fileinfo extension
	 * @param string $fileName
	 * @return string
	 * @throws ValidationFailure exception if type detection fails
	 */
	private function detectMimeType($fileName) 
	{
		if ( ! function_exists('finfo_open')) {
			throw new Exception\RuntimeException('Fileinfo extension is not found, failed to detect uploaded file MIME type');
		}
		
		$finfoPointer = finfo_open(FILEINFO_MIME_TYPE);
		
		$fileInfo = finfo_file($finfoPointer, $fileName);
		
		finfo_close($finfoPointer);
		
		if (empty($fileInfo)) {
			throw new Exception\ValidationFailure('Failed to detect uploaded file MIME type');
		}
		
		return $fileInfo;
	}
}
