<?php

namespace Supra\Package\Cms\FileStorage\ImageProcessor\Adapter;

interface ImageProcessorAdapterInterface
{
	/**
	 * Checks, weither image processing library is available for use
	 */
	public static function isAvailable();
	
	public function doResize($sourceName, $targetName, $width, $height, array $sourceDimensions);
	public function doCrop($sourceName, $targetName, $width, $height, $x, $y);
	public function doRotate($sourceName, $targetName, $degrees);
}