<?php

namespace Supra\Editable;

/**
 * Html editable content, extends string so the string could be extended to HTML content
 */
class Html extends String
{
	const EDITOR_NAME = 'html';
	
	/**
	 * Default filter classes for content by action
	 * @var array
	 */
	protected static $defaultFilters = array(
		self::ACTION_VIEW => 'Supra\Editable\Filter\Raw',
		// TODO: currently doesn't do anything
		self::ACTION_EDIT => 'Supra\Editable\Filter\EditableHtml',
		self::ACTION_PREVIEW => 'Supra\Editable\Filter\Raw'
	);

	/**
	 * Get JavaScript editor name
	 * @return string
	 */
	public function getEditorName()
	{
		return self::EDITOR_NAME;
	}
}
