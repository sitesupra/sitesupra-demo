<?php

namespace Supra\Controller\Pages\Response\PlaceHoldersContainer;

use Supra\Response\ResponseInterface;

/**
 * 
 */
class PlaceHoldersContainerResponseEdit extends PlaceHoldersContainerResponse
{
	/**
	 * Flush the response to another response object
	 * @param ResponseInterface $response
	 */
	public function flushToResponse(ResponseInterface $response)
	{
		$containerName = $this->getContainer();
		
		$response->output('<div id="content_' . $containerName 
				. '" class="yui3-content yui3-content-list yui3-content-list-' 
				. $containerName . '">');
		
		parent::flushToResponse($response);
		
		$response->output('</div>');
	}
}
