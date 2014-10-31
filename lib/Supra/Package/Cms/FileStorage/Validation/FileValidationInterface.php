<?php

namespace Supra\Package\Cms\FileStorage\Validation;

use Supra\Package\Cms\FileStorage\Exception;
use Supra\Package\Cms\Entity\File;

interface FileValidationInterface
{
	public function validateFile(File $file, $sourceFilePath = null);
}
