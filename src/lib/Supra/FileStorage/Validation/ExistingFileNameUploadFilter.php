<?php

namespace Supra\FileStorage\Validation;

use Supra\FileStorage\Exception;
use Supra\FileStorage\Entity\Abstraction\File;

/**
 * Existing file or folder name validation class
 */
class ExistingFileNameUploadFilter extends FileFolderSharedValidation
{
	const EXCEPTION_MESSAGE_KEY = 'medialibrary.validation_error.already_exists';
	
	/**
	 * Shared validation function
	 * @param File $file
	 */
	public function validate(File $entity, $typeName, $sourceFilePath = null)
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
					\Log::info($message);
					
					throw new Exception\DuplicateFileNameException(self::EXCEPTION_MESSAGE_KEY, $message);
				}
			}
		}
	}

}