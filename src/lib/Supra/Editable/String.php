<?php

namespace Supra\Editable;

/**
 * String editable content
 */
class String extends EditableAbstraction
{
	const EDITOR_TYPE = 'String';
	const EDITOR_INLINE_EDITABLE = false;
	
	/**
	 * Default filter classes for content by action
	 * @var array
	 */
	protected static $defaultFilters = array(
		'Supra\Editable\Filter\EscapeHtml',
	);
	
	/**
	 * Return editor type
	 * @return string
	 */
	public function getEditorType()
	{
		return self::EDITOR_TYPE;
	}
	
	/**
	 * {@inheritdoc}
	 * @return boolean
	 */
	public function isInlineEditable()
	{
		return self::EDITOR_INLINE_EDITABLE;
	}
	
}
