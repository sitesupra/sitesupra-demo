<?php

namespace Supra\Controller\Pages\Response\Block;

use Supra\Response\ResponseInterface,
		Supra\Editable\EditableAbstraction;

/**
 * Response for block in edit mode
 */
class ResponseEdit extends Response
{
	/**
	 * Editable filter action
	 * @var string
	 */
	const EDITABLE_FILTER_ACTION = EditableAbstraction::ACTION_EDIT;
	
	/**
	 * Flush the response to another response object
	 * @param ResponseInterface $response
	 */
	public function flushToResponse(ResponseInterface $response)
	{
		//TODO: pass the block 
		
//		$response->output('<div class="block">');
		parent::flushToResponse($response);
//		$response->output('</div>');
	}
}
