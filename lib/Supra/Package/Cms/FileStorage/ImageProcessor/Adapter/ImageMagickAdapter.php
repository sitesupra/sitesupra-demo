<?php

namespace Supra\Package\Cms\FileStorage\ImageProcessor\Adapter;
use Supra\Package\Cms\FileStorage\FileStorage;

/**
 * Wrapper for php imagick library
 */
class ImageMagickAdapter extends ImageProcessorAdapterAbstract
{

	const IMAGETYPE_PNG = 'png',
			IMAGETYPE_JPEG = 'jpeg',
			IMAGETYPE_GIF = 'gif',
			IMAGETYPE_BMP = 'bmp';

	/**
	 * @var FileStorage
	 */
	protected $fileStorage;

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
	 * @inheritdoc
	 */
	public static function isAvailable()
	{
		// checks only for PHP Imagick extension installed
		// will fail, if ImageMagick isn't available 
		if (extension_loaded('imagick')) {
			return true;
		}
		
		return false;
	}
	
	/**
	 * @param string $fileName
	 * @return \Imagick
	 */
	protected function createFromFile($fileName)
	{
		$image = new \Imagick;
		$image->readImage($fileName);
		
		return $image;
	}
	
	/**
	 * @param \Imagick $image
	 * @param string $fileName
	 */
	protected function saveToFile(\Imagick $image, $fileName)
	{
		$image->stripImage();
		
		$image->writeImage($fileName);
		$image->destroy();
	}
	
	/**
	 * @param string $sourceName
	 * @param string $targetName
	 * @param int $width
	 * @param int $height
	 */
	public function doResize($sourceName, $targetName, $width, $height, array $sourceDimensions)
	{
		$image = $this->createFromFile($sourceName);
		
		//$image->setGravity(\Imagick::GRAVITY_CENTER);
		$image->cropThumbnailImage($width, $height);
		
		$this->saveToFile($image, $targetName);
	}
	
	/**
	 * @param string $sourceName
	 * @param string $targetName
	 * @param int $width
	 * @param int $height
	 * @param int $x
	 * @param int $y
	 */
	public function doCrop($sourceName, $targetName, $width, $height, $x, $y)
	{
		$image = $this->createFromFile($sourceName);
		
		$image->cropImage($width, $height, $x, $y);
		
		$this->saveToFile($image, $targetName);
	}
	
	/**
	 * 
	 * @param type $sourceName
	 * @param type $targetName
	 * @param type $degrees
	 */
	public function doRotate($sourceName, $targetName, $degrees)
	{
		$image = $this->createFromFile($sourceName);
		
		$image->rotateImage(new \ImagickPixel('none'), $degrees);
		
		$this->saveToFile($image, $targetName);
	}
	
}