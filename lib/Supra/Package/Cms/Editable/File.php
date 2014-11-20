<?php

namespace Supra\Package\Cms\Editable;

/**
 * File editable
 */
class File extends Editable
{
	const EDITOR_TYPE = 'File';
	
	/**
	 * Return editor type
	 * @return string
	 */
	public function getEditorType()
	{
		return static::EDITOR_TYPE;
	}
}
