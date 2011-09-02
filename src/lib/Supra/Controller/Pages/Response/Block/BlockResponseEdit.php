<?php

namespace Supra\Controller\Pages\Response\Block;

use Supra\Response\ResponseInterface;
use Supra\Editable\EditableAbstraction;
use Supra\Controller\Pages\Entity;

/**
 * Response for block in edit mode
 */
class BlockResponseEdit extends BlockResponse
{
	/**
	 * Flush the response to another response object
	 * @param ResponseInterface $response
	 */
	public function flushToResponse(ResponseInterface $response)
	{
		$block = $this->getBlock();
		$blockId = $block->getId();
		
		// Normalize block name
		$blockName = $block->getComponentName();
		
		$response->output('<div id="content_' . $blockName . '_' . $blockId
				. '" class="yui3-page-content yui3-page-content-' . $blockName 
				. ' yui3-page-content-' . $blockName . '-' . $blockId . '">');
		
		parent::flushToResponse($response);
		
		$response->output('</div>');
	}
	
}
