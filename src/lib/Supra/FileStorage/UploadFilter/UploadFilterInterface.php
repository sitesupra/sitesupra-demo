<?php

namespace Supra\FileStorage\UploadFilter;

interface UploadFilterInterface
{
	public function validate(\Supra\FileStorage\Entity\File $file);
}
