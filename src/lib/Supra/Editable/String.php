<?php

namespace Supra\Editable;

/**
 * String editable content
 */
class String extends EditableAbstraction
{
	const EDITOR_NAME = 'string';
	
	/**
	 * Default filter classes for content by action
	 * @var array
	 */
	protected static $defaultFilters = array(
		self::ACTION_VIEW => 'Supra\Editable\Filter\EscapeHtml',
		self::ACTION_EDIT => 'Supra\Editable\Filter\EscapeHtml',
		self::ACTION_PREVIEW => 'Supra\Editable\Filter\EscapeHtml'
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
