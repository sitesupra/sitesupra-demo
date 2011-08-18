<?php

namespace Supra\Response;

use Supra\Request\HttpRequest;
use Supra\Controller\Exception\ResourceNotFoundException;

/**
 * Used to respond with file contents
 */
class FileContentResponse extends HttpResponse
{
	/**
	 * @param HttpRequest $request
	 * @param string $file
	 */
	public function outputFile(HttpRequest $request, $file)
	{
		if ( ! is_file($file) || ! is_readable($file)) {
			throw new ResourceNotFoundException("File '$file' does not exist or is not readable");
		}
		
		$ifModifiedSince = $request->getServerValue('HTTP_IF_MODIFIED_SINCE');
		$ifNoneMatch = $request->getServerValue('HTTP_IF_NONE_MATCH');

		$timestamp = filemtime($file);

		$gmtMTime = gmdate('r', $timestamp);
		$eTag = md5($timestamp . $file);

		if ($ifModifiedSince == $gmtMTime || $ifNoneMatch == $eTag) {
			$this->setCode(304);

			return;
		}

		$this->header('ETag', $eTag);
		$this->header('Last-Modified', $gmtMTime);
		
		$this->output(file_get_contents($file));
	}

}
