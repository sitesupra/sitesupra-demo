<?php

namespace Supra\Validation;

class ExtensionUploadFilter extends UploadFilterAbstract implements \Supra\Validation\UploadFilterInterface
{
	/**
	 * Validates file extension
	 * @param \Supra\FileStorage\Entity\File $file 
	 */
	public function validate(\Supra\FileStorage\Entity\File $file)
	{
		$result = $this->checkList($file->getExtension());
		if( ! $result) {
			$message = 'File extension "'.$file->getExtension().'" is not allowed';
			throw new \Supra\Validation\UploadFilterException($message);
			\Log::error($message);
		}
	}
	
}

