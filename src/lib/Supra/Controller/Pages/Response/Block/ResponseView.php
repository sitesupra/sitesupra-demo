<?php

namespace Supra\Controller\Pages\Response\Block;


use Supra\Editable\EditableAbstraction;

/**
 * Response for block in view mode
 */
class ResponseView extends Response
{
	/**
	 * Editable filter action
	 * @var string
	 */
	const EDITABLE_FILTER_ACTION = EditableAbstraction::ACTION_VIEW;
}
