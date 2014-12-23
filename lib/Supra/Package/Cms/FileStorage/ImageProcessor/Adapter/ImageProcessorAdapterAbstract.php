<?php

namespace Supra\Package\Cms\FileStorage\ImageProcessor\Adapter;

use Supra\Package\Cms\FileStorage\Exception\ImageProcessorException;
use Supra\Package\Cms\FileStorage\ImageInfo;
use Supra\Package\Cms\FileStorage\FileStorage;

abstract class ImageProcessorAdapterAbstract implements ImageProcessorAdapterInterface
{
	/**
	 * @var FileStorage
	 */
	protected $fileStorage;

	/**
	 * @param FileStorage $fileStorage
	 */
	public function setFileStorage(FileStorage $fileStorage)
	{
		$this->fileStorage = $fileStorage;
	}

	/**
	 * @return FileStorage
	 */
	public function getFileStorage()
	{
		return $this->fileStorage;
	}

	/**
	 * @param string $fileName
	 * @return ImageInfo
	 * @throws ImageProcessorException
	 */
	protected function getImageInfo($fileName)
	{
		$info = new ImageInfo($fileName);

		if ($info->hasError()) {
			throw new ImageProcessorException('File ' . $fileName . ' not found or is not readable. ' . $info->getError());
		}

		return $info;
	}
}