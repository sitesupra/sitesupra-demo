<?php

namespace Supra\FileStorage\Helpers;
use Supra\FileStorage\Exception;

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
		return 'File contains depricated characters '
				. implode(', ', $this->characterList)
				. ' or starts with a dot.';
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

		$depricatedCharacters = preg_match($pattern, $name, $depricatedCharacters);
		$fistDotMatch = preg_match('/^\./', $name, $fistDotMatch);

		if (( ! empty($depricatedCharacters)) || ( ! empty($fistDotMatch))) {
			return false;
		}

		return true;
	}

}
