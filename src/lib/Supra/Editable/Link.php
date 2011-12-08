<?php

namespace Supra\Editable;

/**
 * Link
 * @TODO: what could be a default value for link?
 * @TODO: possibility to limit to file/image/internal/external links
 */
class Link extends EditableAbstraction
{
	const EDITOR_TYPE = 'Link';
	const EDITOR_INLINE_EDITABLE = false;
	
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
