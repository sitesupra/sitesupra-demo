<?php

namespace Supra\Controller\Pages\Response\PlaceHolder;

use Supra\Response\ResponseInterface;
use Supra\Editable\EditableAbstraction;

/**
 * Response for place holder edit mode
 */
class PlaceHolderResponseEdit extends PlaceHolderResponse
{
	/**
	 * Flush the response to another response object
	 * @param ResponseInterface $response
	 */
	public function flushToResponse(ResponseInterface $response)
	{
		$placeHolder = $this->getPlaceHolder();
		
		$placeHolderName = $placeHolder->getName();
		
		$response->output('<div id="content_list_' . $placeHolderName 
				. '" class="yui3-content yui3-content-list yui3-content-list-' 
				. $placeHolderName . '">');
		
		parent::flushToResponse($response);
		
		$response->output('</div>');
	}
}
