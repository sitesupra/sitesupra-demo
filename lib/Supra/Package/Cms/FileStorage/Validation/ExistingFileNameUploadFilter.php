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

use Supra\Package\Cms\FileStorage\Exception;
use Supra\Package\Cms\Entity\Abstraction\File as FileAbstraction;

/**
 * Existing file or folder name validation class
 */
class ExistingFileNameUploadFilter extends FileFolderSharedValidation
{
	const EXCEPTION_MESSAGE_KEY = 'medialibrary.validation_error.already_exists';
	
	/**
	 * Shared validation function
	 * @param FileAbstraction $file
	 */
	public function validate(FileAbstraction $entity, $typeName, $sourceFilePath = null)
	{
		$siblings = $entity->getSiblings();
		$creatingFilename = $entity->getFileName();

		foreach ($siblings as $record) {
			/* @var $record File */
			
			if ( ! $record->equals($entity)) {
				$recordName = $record->getFileName();
				
				$creatingFilename = mb_strtolower($creatingFilename);
				$recordName = mb_strtolower($recordName);

				if ($creatingFilename == $recordName) {
					$message = $typeName . ' with this name already exists.';
					
					throw new Exception\DuplicateFileNameException(self::EXCEPTION_MESSAGE_KEY, $message);
				}
			}
		}
	}

}