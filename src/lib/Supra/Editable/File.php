<?php

namespace Supra\Editable;

/**
 * File editable
 */
class File extends EditableAbstraction
{
	const EDITOR_TYPE = 'File';
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
