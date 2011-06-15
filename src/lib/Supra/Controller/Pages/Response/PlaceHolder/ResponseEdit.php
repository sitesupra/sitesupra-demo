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
		$response->output('<div class="place-holder">');
		parent::flushToResponse($response);
		$response->output('</div>');
	}
}
