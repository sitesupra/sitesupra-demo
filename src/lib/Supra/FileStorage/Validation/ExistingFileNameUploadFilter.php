<?php

namespace Supra\FileStorage\Validation;
use Supra\FileStorage\Exception;
/**
 * Existing file or folder name validation class
 */
class ExistingFileNameUploadFilter implements FileValidationInterface, FolderValidationInterface
{

	public function validateFile(\Supra\FileStorage\Entity\File $file)
	{
		$this->validate($file, 'File');
	}

	public function validateFolder(\Supra\FileStorage\Entity\Folder $folder)
	{
		$this->validate($folder, 'Folder');
	}

	private function validate($entity, $type)
	{
		$siblings = $entity->getSiblings();
		$creatingFilename = $entity->getName();

		foreach ($siblings as $record) {
			if ( ! $record->equals($entity)) {
				$recordName = $record->getName();
				if ($creatingFilename == $recordName) {
					$message = $type . ' with such name already exists.';
					\Log::error($message);
					throw new Exception\UploadFilterException($message);
					
				}
			}
		}
	}

}