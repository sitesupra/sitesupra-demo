<?php

namespace Supra\Package\Cms\FileStorage\Exception;

/**
 * FileUploadException
 *
 * @author dmitryp
 */
class FileUploadException extends \RuntimeException implements FileStorageException
{
	/**
	 * Default error code
	 */
	const UPPLOAD_ERR_DEFAULT = 100;

	/**
	 * Default error message
	 * @var string 
	 */
	public static $default = 'An unknown error happened when trying to upload file';

	/**
	 * Error messages
	 * @var array 
	 */
	public static $messages = array(
		UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive',
		UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
		UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
		UPLOAD_ERR_NO_FILE => 'No file was uploaded',
		UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
		UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
		UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload',
	);

	/**
	 * Throws new FileUploadException with message and code based on passed code
	 * @param integer $code
	 * @return FileUploadException or LogicException if code 0
	 */
	public static function fire($code)
	{
		if ($code == 0) {
			return new LogicException('Error code 0 means upload was successful');
		}

		if ( ! in_array($code, array_keys(self::$messages))) {
			return new self(self::$default, self::UPPLOAD_ERR_DEFAULT);
		}

		return new self(self::$messages[$code], $code);
	}

}