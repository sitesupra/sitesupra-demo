<?php

namespace Supra\FileStorage\Helpers;

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
			$message = 'File contains depricated characters ' . implode(', ', $this->characterList) . ' or starts with a dot.';
			throw new FileStorageHelpersException($message);
			\Log::error($message);
		}
	}

}
