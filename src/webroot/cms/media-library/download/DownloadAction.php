<?php

namespace Supra\Cms\MediaLibrary\Download;

use Supra\Cms\MediaLibrary\MediaLibraryAbstractAction;
use Supra\Controller\Exception\ResourceNotFoundException;
use Supra\Response;
use Supra\Request;

/**
 * File download action
 * @method Response\FileContentResponse getResponse()
 */
class DownloadAction extends MediaLibraryAbstractAction
{
	/**
	 * @param Request\RequestInterface $request
	 * @return Response\FileContentResponse
	 */
	public function createResponse(Request\RequestInterface $request)
	{
		$response = new Response\FileContentResponse();
		
		return $response;
	}
	
	/**
	 * Main download action
	 */
	public function execute()
	{
		$response = $this->getResponse();
		$request = $this->getRequest();
		
		$actions = $request->getActions(null);
		
		if (count($actions) != 1) {
			throw new ResourceNotFoundException("Wrong request path received for file download action");
		}
		
		$requestedFileName = $actions[0];
		
		$file = $this->getFile();

		$mimeType = $file->getMimeType();
		$fileName = $file->getFileName();
		
		//TODO: is case sensitive comparison OK?
		if ($fileName !== $requestedFileName) {
			throw new ResourceNotFoundException("Requested file name does not match file name on the server");
		}
		
		$path = $this->fileStorage->getFilesystemPath($file);

		if ( ! empty($mimeType)) {
			$response->header('Content-type', $mimeType);
		}

		$response->header('Content-Disposition', 'attachment');
		$response->header('Content-Transfer-Encoding', 'binary');
		$response->header('Pragma', 'private');
		$response->header('Cache-Control', 'private, must-revalidate');

		$response->outputFile($request, $path);
	}
}
