<?php

namespace Supra\Editable;

/**
 * Gallery property
 */
class Gallery extends EditableAbstraction
{
	public function getEditorType()
	{
		return 'Gallery';
	}

	public function isInlineEditable()
	{
		false;
	}
}
