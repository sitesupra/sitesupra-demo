<?php

namespace Supra\FileStorage\Validation;

use Supra\FileStorage\Exception;
use Supra\FileStorage\Entity\Folder;

interface FolderValidationInterface
{
	public function validateFolder(Folder $folder);
}
