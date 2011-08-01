<?php

namespace Supra\FileStorage\UploadFilter;

interface FileValidationInterface
{
	public function validateFile(\Supra\FileStorage\Entity\File $file);
}
