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

namespace Supra\Package\Cms\FileStorage\Validation;

use Supra\Package\Cms\Entity\Abstraction\File;
use Supra\Package\Cms\FileStorage\Exception;
use Supra\Package\Cms\FileStorage\Helpers;

/**
 * File and folder name validation class. Check folder and file name on forbidden characters
 */
class FileNameUploadFilter extends FileFolderSharedValidation
{
	const EXCEPTION_MESSAGE_KEY = 'medialibrary.validation_error.invalid_name';
	
	/**
	 * Validates filename for files and folders
	 * @param File $file
	 * @param string $typeName
	 */
	public function validate(File $file, $typeName, $sourceFilePath = null)
	{
		$name = $file->getFileName();
		$fileNameHelper = new Helpers\FileNameValidationHelper();
		$result = $fileNameHelper->validate($name);
		
		if( ! $result) {
			$message = $fileNameHelper->getErrorMessage();
			
			throw new Exception\UploadFilterException(self::EXCEPTION_MESSAGE_KEY, $message);
		}
	}
	
}