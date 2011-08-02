<?php

namespace Supra\FileStorage\Validation;
use Supra\FileStorage\Exception;

interface FileValidationInterface
{
	public function validateFile(\Supra\FileStorage\Entity\File $file);
}
