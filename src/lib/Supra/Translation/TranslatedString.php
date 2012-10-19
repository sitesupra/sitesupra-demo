<?php

namespace Supra\Translation;

/**
 * Marks text as translated already
 */
class TranslatedString
{
	private $string;

	function __construct($string)
	{
		$this->string = $string;
	}

	public function __toString()
	{
		return $this->string;
	}

}
