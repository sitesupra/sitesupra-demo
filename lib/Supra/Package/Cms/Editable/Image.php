<?php

namespace Supra\Package\Cms\Editable;

/**
 * Image editable
 */
class Image extends Editable
{
	const EDITOR_TYPE = 'Image';

	/**
	 * Return editor type
	 * @return string
	 */
	public function getEditorType()
	{
		return static::EDITOR_TYPE;
	}
}
