<?php

namespace Supra\Editable;

/**
 * Image editable
 */
class InlineMedia extends EditableAbstraction
{
	const EDITOR_TYPE = 'InlineMedia';
	const EDITOR_INLINE_EDITABLE = true;
	
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
