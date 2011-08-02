<?php

namespace Supra\FileStorage\Validation;
use Supra\FileStorage\Exception;

interface FolderValidationInterface
{
	public function validateFolder(\Supra\FileStorage\Entity\Folder $folder);
}
