<?php

namespace Supra\Package\Cms\FileStorage\Validation;

use Supra\Package\Cms\FileStorage\Exception;
use Supra\Package\Cms\Entity\Folder;

interface FolderValidationInterface
{
	public function validateFolder(Folder $folder);
}
