<?php

namespace Supra\Form\Transformer;

use Symfony\Component\Form\DataTransformerInterface;
use Supra\FileStorage\Entity\File;
use Supra\ObjectRepository\ObjectRepository;

/**
 * Description of FileIdToRecordTransformer
 */
class FileIdToRecordTransformer implements DataTransformerInterface
{
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

		return $value['id'];
	}

	/**
	 * @param string $value
	 * @return null|File
	 */
	public function transform($value)
	{
		if (is_null($value)) {
			return null;
		}

		$storage = ObjectRepository::getFileStorage($this);
		$file = $storage->find($value, File::CN());

		if (is_null($file)) {
			return null;
		}

		$info = $storage->getFileInfo($file);

		return $info;
	}

}
