<?php

namespace Supra\Controller\Pages\Response\PlaceHolderGroup;

/**
 * 
 */
class PlaceHolderGroupResponseEdit extends PlaceHolderGroupResponse
{
	/**
	 * @param ResponseInterface $response
	 */
	public function flushToResponse(\Supra\Response\ResponseInterface $response)
	{
		$response->output('<div id="content_' . $this->groupName 
				. '" class="yui3-content yui3-content-list yui3-content-list-' 
				. $this->groupName . '">');
		
		parent::flushToResponse($response);
		
		$response->output('</div>');
	}
}
