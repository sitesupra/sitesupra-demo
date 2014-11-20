<?php

namespace Supra\Package\Cms\Editable;

class Gallery extends Editable
{
	const EDITOR_TYPE = 'Gallery';

	/**
	 * {@inheritDoc}
	 */
	public function getEditorType()
	{
		return static::EDITOR_TYPE;
	}
}
