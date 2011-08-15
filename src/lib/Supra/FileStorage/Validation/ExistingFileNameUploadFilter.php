<?php

namespace Supra\FileStorage\Validation;

use Supra\FileStorage\Exception;
use Supra\FileStorage\Entity\Abstraction\File;

/**
 * Existing file or folder name validation class
 */
class ExistingFileNameUploadFilter extends FileFolderSharedValidation
{
	/**
	 * Shared validation function
	 * @param File $file
	 */
	public function validate(File $entity, $typeName)
	{
		$siblings = $entity->getSiblings();
		$creatingFilename = $entity->getName();

		foreach ($siblings as $record) {
			if ( ! $record->equals($entity)) {
				$recordName = $record->getName();
				if ($creatingFilename == $recordName) {
					$message = $typeName . ' with such name already exists.';
					\Log::info($message);
					
					throw new Exception\UploadFilterException($message);
				}
			}
		}
	}

}