<?php

namespace Supra\Editable;

/**
 * String editable content
 */
class BlockBackground extends EditableAbstraction
{
	const EDITOR_TYPE = 'BlockBackground';
	const EDITOR_INLINE_EDITABLE = false;
	
	/**
	 * Return editor type
	 * @return string
	 */
	public function getEditorType()
	{
		return static::EDITOR_TYPE;
	}
	
	/**
	 * {@inheritdoc}
	 * @return boolean
	 */
	public function isInlineEditable()
	{
		return static::EDITOR_INLINE_EDITABLE;
	}
	
}
