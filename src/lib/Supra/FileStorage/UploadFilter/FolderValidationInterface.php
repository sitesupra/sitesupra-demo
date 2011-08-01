<?php

namespace Supra\FileStorage\UploadFilter;

interface FolderValidationInterface
{
	public function validateFolder(\Supra\FileStorage\Entity\Folder $folder);
}
