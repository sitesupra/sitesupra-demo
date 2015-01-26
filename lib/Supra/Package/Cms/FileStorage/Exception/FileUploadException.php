<?php

/*
 * Copyright (C) SiteSupra SIA, Riga, Latvia, 2015
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 */

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