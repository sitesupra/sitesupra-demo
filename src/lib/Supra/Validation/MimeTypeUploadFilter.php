<?php

namespace Supra\Validation;

class MimeTypeUploadFilter implements \Supra\Validation\UploadFilter
{
	/**
	 * Validates file mimetype
	 * @param \Supra\FileStorage\Entity\File $file 
	 */
	public function validate(\Supra\FileStorage\Entity\File $file)
	{
		$result = $this->checkList($file->getMimeType());
		if( ! $result) {
			$message = 'File mimetype "'.$file->getMimeType().'" is not allowed';
			throw new \Supra\Validation\UploadFilterException($message);
			\Log::error($message);
		}
	}

}
