<?php

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
