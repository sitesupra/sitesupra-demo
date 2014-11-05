<?php

namespace Supra\Package\Cms\FileStorage\ImageProcessor\Adapter;

use Supra\Package\Cms\FileStorage\FileStorage;

interface ImageProcessorAdapterInterface
{
	/**
	 * Checks, whether image processing library is available for use
	 */
	public static function isAvailable();
	
	public function doResize($sourceName, $targetName, $width, $height, array $sourceDimensions);
	public function doCrop($sourceName, $targetName, $width, $height, $x, $y);
	public function doRotate($sourceName, $targetName, $degrees);

	public function setFileStorage(FileStorage $storage);

	/**
	 * @return FileStorage
	 */
	public function getFileStorage();
}