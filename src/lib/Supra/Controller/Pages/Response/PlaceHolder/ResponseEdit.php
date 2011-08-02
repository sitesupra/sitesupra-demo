<?php

namespace Supra\Controller\Pages\Response\PlaceHolder;

use Supra\Response\ResponseInterface;
use Supra\Editable\EditableAbstraction;

/**
 * Response for place holder edit mode
 */
class ResponseEdit extends Response
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
				. '" class="yui3-page-content yui3-page-content-list yui3-page-content-list-' 
				. $placeHolderName . '">');
		
		parent::flushToResponse($response);
		
		$response->output('</div>');
	}
}
