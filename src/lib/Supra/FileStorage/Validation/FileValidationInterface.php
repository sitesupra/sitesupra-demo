<?php

namespace Supra\FileStorage\Validation;

use Supra\FileStorage\Exception;
use Supra\FileStorage\Entity\File;

interface FileValidationInterface
{
	public function validateFile(File $file);
}
