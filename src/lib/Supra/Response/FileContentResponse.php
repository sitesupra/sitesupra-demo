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
	 * File path
	 * @var string
	 */
	private $filename;
	
	/**
	 * @param HttpRequest $request
	 * @param string $file
	 */
	public function outputFile(HttpRequest $request, $file)
	{
		if ( ! is_file($file) || ! is_readable($file)) {
			throw new ResourceNotFoundException("File '$file' does not exist or is not readable");
		}
		
		$this->filename = $file;
		
		$ifModifiedSince = $request->getServerValue('HTTP_IF_MODIFIED_SINCE');
		$ifNoneMatch = $request->getServerValue('HTTP_IF_NONE_MATCH');

		$timestamp = filemtime($file);

		$gmtMTime = gmdate('r', $timestamp);
		$eTag = md5($timestamp . $file);

		if ($ifModifiedSince == $gmtMTime || $ifNoneMatch == $eTag) {
			$this->setCode(self::STATUS_NOT_MODIFIED);

			return;
		}

		$this->header('ETag', $eTag);
		$this->header('Last-Modified', $gmtMTime);
	}
	
	/**
	 * Flushes file as a stream
	 */
	public function flush()
	{
		// Stop output buffering
		ob_end_flush();
		readfile($this->filename);
		flush();
	}
	
	/**
	 * Reads file for output
	 */
	public function __toString()
	{
		echo file_get_contents($this->filename);
	}
	
}
