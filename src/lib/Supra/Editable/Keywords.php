<?php

namespace Supra\Editable;

/**
 * Textarea editable type
 */
class Keywords extends String
{
	const EDITOR_TYPE = 'Keywords';
	const EDITOR_INLINE_EDITABLE = false;

	const DELIMITER = ';';

	/**
	 * Defines auto-complete values retreival URL
	 * If empty, autocomplete is off
	 *
	 * @var string
	 */
	protected $autoCompleteUrl = null;

	/**
	 * @TODO: would be nice, if this thing would support named routes and params.
	 *
	 * @param string $autoComplete
	 */
	public function setAutoCompleteUrl($autoCompleteUrl)
	{
		$this->autoCompleteUrl = $autoCompleteUrl;
	}

	/**
	 * @return array
	 */
	public function getAdditionalParameters()
	{
		return array('autoCompleteUrl' => $this->autoCompleteUrl);
	}
}
