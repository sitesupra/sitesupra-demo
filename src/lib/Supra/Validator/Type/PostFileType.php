<?php

namespace Supra\Validator\Type;

use Supra\Validator\Exception;

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
			switch ($value['error']) {
				case UPLOAD_ERR_INI_SIZE:
					$thrownError = 'The uploaded file exceeds the upload_max_filesize directive';
				break;
			
				case UPLOAD_ERR_FORM_SIZE:
					$thrownError = 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
				break;
			
				case UPLOAD_ERR_PARTIAL:
					$thrownError = 'The uploaded file was only partially uploaded';
				break;
			
				case UPLOAD_ERR_NO_FILE:
					$thrownError = 'No file was uploaded';
				break;
			
				case UPLOAD_ERR_NO_TMP_DIR:
					$thrownError = 'Missing a temporary folder';
				break;
			
				case UPLOAD_ERR_CANT_WRITE:
					$thrownError = 'Failed to write file to disk';
				break;
					
				case UPLOAD_ERR_EXTENSION:
					$thrownError = 'A PHP extension stopped the file upload';
				break;
			
				default:
					$thrownError = 'An unknown error happened when trying to upload file';
			}
			
			throw new Exception\ValidationFailure($thrownError);
		}
		
		if ( ! is_readable($value['tmp_name'])) {
			throw new Exception\ValidationFailure('File is not accessible');
		}
		
		if ( ! empty($additionalParams)) {
			$additionalParams = array_shift($additionalParams);
			
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
