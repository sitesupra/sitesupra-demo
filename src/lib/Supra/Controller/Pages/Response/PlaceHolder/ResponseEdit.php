<?php

namespace Supra\Controller\Pages\Response\PlaceHolder;

use Supra\Response\ResponseInterface,
		Supra\Editable\EditableAbstraction;

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
		//TODO: hardcoded
		static $ids = array('sidebar', 'inner', 'footer');
		$id = array_shift($ids);
		
		$response->output('<div id="content_list_' . $id . '" class="yui3-page-content yui3-page-content-list yui3-page-content-list-' . $id . '">');
		parent::flushToResponse($response);
		$response->output('</div>');
	}
}
