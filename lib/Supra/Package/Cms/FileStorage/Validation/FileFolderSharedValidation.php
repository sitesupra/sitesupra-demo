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

use Supra\Package\Cms\Entity;

/**
 * For validation classes which validates files and folders the same
 */
abstract class FileFolderSharedValidation implements FileValidationInterface,
		FolderValidationInterface
{
	const TYPE_FILE = 'File';
	const TYPE_FOLDER = 'Folder';
	
	/**
	 * Shared validation function
	 * @param Entity\Abstraction\File $file
	 */
	abstract public function validate(Entity\Abstraction\File $file, $typeName, $sourceFilePath = null);
	
	/**
	 * Calls shared validation method
	 * @param Entity\File $file
	 */
	public function validateFile(Entity\File $file, $sourceFilePath = null)
	{
		$this->validate($file, self::TYPE_FILE, $sourceFilePath);
	}

	/**
	 * Calls shared validation method
	 * @param Entity\Folder $folder
	 */
	public function validateFolder(Entity\Folder $folder)
	{
		$this->validate($folder, self::TYPE_FOLDER);
	}

}
