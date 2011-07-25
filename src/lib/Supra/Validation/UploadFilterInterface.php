<?php

namespace Supra\Validation;

interface UploadFilterInterface
{
	public function validate(\Supra\FileStorage\Entity\File $file);
}
