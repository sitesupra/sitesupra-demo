<?php

namespace Supra\Package\Cms\FileStorage\Helpers;

use Supra\Package\Cms\FileStorage\Exception;

class FileNameValidationHelper
{

	/**
	 * Forbidden character list
	 * @var array
	 */
	private $characterList = array(
		'/',
		'|',
		'"',
		':',
		'?',
		'*',
		'<',
		'>',
	);

	public function getErrorMessage()
	{
		return 'Name contains forbidden characters '
				. implode(', ', $this->characterList)
				. ' or starts with a dot or underscore.';
	}

	/**
	 * Validates file or folder name
	 * @param $name
	 */
	public function validate($name)
	{
		$pattern = '/[\\\\\\' . implode('\\', $this->characterList) . ']/i';

		$depricatedCharacters = null;
		$fistDotMatch = null;
		$fistUnderscoreMatch = null;

		$depricatedCharacters = preg_match($pattern, $name, $depricatedCharacters);
		$fistDotMatch = preg_match('/^\./', $name, $fistDotMatch);
		$fistUnderscoreMatch = preg_match('/^\_/', $name, $fistUnderscoreMatch);

		if (( ! empty($depricatedCharacters))
				|| ( ! empty($fistDotMatch))
				|| ( ! empty($fistUnderscoreMatch))) 
		{
			return false;
		}

		return true;
	}

}
