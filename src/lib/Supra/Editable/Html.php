<?php

namespace Supra\Editable;

/**
 * Html editable content, extends string so the string could be extended to HTML content
 */
class Html extends String
{
	const EDITOR_NAME = 'html';
	
	/**
	 * No filtering for HTML
	 * @var array
	 */
	protected static $defaultFilters = array();

	/**
	 * Get JavaScript editor name
	 * @return string
	 */
	public function getEditorName()
	{
		return self::EDITOR_NAME;
	}
}
