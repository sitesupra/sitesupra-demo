<?php

namespace Supra\FileStorage\ImageProcessor\Adapter;

abstract class ImageProcessorAdapterAbstract implements ImageProcessorAdapterInterface
{
	
	/**
	 * @param string $fileName
	 * @return \Supra\FileStorage\ImageInfo
	 * @throws \Supra\FileStorage\Exception\ImageProcessorException
	 */
	protected function getImageInfo($fileName)
	{
		$info = new \Supra\FileStorage\ImageInfo($fileName);

		if ($info->hasError()) {
			throw new \Supra\FileStorage\Exception\ImageProcessorException('File ' . $fileName . ' not found or is not readable. ' . $info->getError());
		}

		return $info;
	}
}