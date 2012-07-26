<?php

namespace Supra\Form\Transformer;

use Symfony\Component\Form\DataTransformerInterface;
use Supra\FileStorage\Entity\File;
use Supra\ObjectRepository\ObjectRepository;

/**
 */
class FileIdListToRecordTransformer implements DataTransformerInterface
{
	private $simpleTransformer;

	public function __construct()
	{
		$this->simpleTransformer = new FileIdToRecordTransformer();
	}

	/**
	 * @param \Supra\FileStorage\Entity\File $value
	 * @return null|string
	 */
	public function reverseTransform($value)
	{
		if (is_null($value)) {
			return null;
		}

		if ( ! is_array($value)) {
			\Log::warn("not an array");
			return null;
		}

		foreach ($value as &$record) {
			$record = $this->simpleTransformer->reverseTransform($record);
		}

		return $value;
	}

	/**
	 * @param array $value
	 * @return null|File
	 */
	public function transform($value)
	{
		if (is_null($value)) {
			return null;
		}

		foreach ($value as &$record) {
			$record = $this->simpleTransformer->transform($record);
		}

		return $value;
	}

}
