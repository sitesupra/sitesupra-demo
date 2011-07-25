<?php

namespace Supra\FileStorage\UploadFilter;

class ExtensionUploadFilter extends BlackWhiteListCheck implements UploadFilterInterface
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
			throw new UploadFilterException($message);
			\Log::error($message);
		}
	}
	
}

