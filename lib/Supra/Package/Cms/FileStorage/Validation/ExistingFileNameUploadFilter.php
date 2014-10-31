<?php

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