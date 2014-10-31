<?php

namespace Supra\Package\Cms\FileStorage;
use Supra\Package\Cms\Entity\Image;

/**
 * Builds info from image entity or full path
 */
class ImageInfo
{

	/**
	 * @var Image
	 */
	protected $image;

	/**
	 * Full system file path
	 * @var string
	 */
	protected $path;

	/**
	 * File size
	 * @var integer
	 */
	protected $size;

	/**
	 * File name with extension
	 * @var string
	 */
	protected $name;

	/**
	 * File system directory
	 * @var string
	 */
	protected $directory;

	/**
	 * File extension
	 * @var string
	 */
	protected $extension;

	/**
	 * Image width
	 * @var integer
	 */
	protected $width;

	/**
	 * Image height
	 * @var integer 
	 */
	protected $height;

	/**
	 * IMAGETYPE_XXX
	 * @var integer 
	 */
	protected $type;

	/**
	 * File mime
	 * @var string 
	 */
	protected $mime;

	/**
	 * Image channels 
	 * @var integer
	 */
	protected $channels;

	/**
	 * Image bits
	 * @var integer
	 */
	protected $bits;

	/**
	 * Error while processing 
	 * @var string
	 */
	protected $error;

	/**
	 * Builds info from image entity or full path
	 * @param Image|string $image
	 */
	public function __construct($image)
	{
		if ($image instanceof Image) {
			$this->image = $image;
			throw new \Exception('Please give me a filestorage');
			$image = $fileStorage->getImagePath($image);
		}

		$this->process($image);
	}

	/**
	 * Builds info from image full path
	 * @param string $filePath 
	 */
	public function process($filePath)
	{
		if ( ! is_string($filePath) && ! file_exists($filePath) && ! is_readable($filePath)) {
			$this->error = 'Failed to get image path';
			return;
		}

		$imageInfo = getimagesize($filePath);

		if ($imageInfo === false) {
			$this->error = 'Failed to get image information from path "' . $filePath . '"';
			return;
		}

		$this->width = $imageInfo[0];
		$this->height = $imageInfo[1];
		$this->type = $imageInfo[2];
		$this->bits = $imageInfo['bits'];
		$this->channels = (isset($imageInfo['channels']) ? $imageInfo['channels'] : null);
		$this->mime = $imageInfo['mime'];
		$this->path = $filePath;
		$this->size = filesize($filePath);
		$pathInfo = pathinfo($filePath);
		$this->name = $pathInfo['basename'];
		$this->directory = $pathInfo['dirname'];
		$this->extension = (isset($pathInfo['extension']) ? $pathInfo['extension'] : null);
	}

	/**
	 * Returns image info in array
	 * @return array
	 */
	public function toArray()
	{
		return array(
			'width' => $this->width,
			'height' => $this->height,
			'type' => $this->type,
			'bits' => $this->bits,
			'channels' => $this->channels,
			'mime' => $this->mime,
			'path' => $this->path,
			'size' => $this->size,
			'name' => $this->name,
			'directory' => $this->directory,
			'extension' => $this->extension,
		);
	}

	/**
	 * Returns Image entity instance if info was built from entity
	 * @return Image
	 */
	public function getImage()
	{
		return $this->image;
	}

	/**
	 * Returns full system file path
	 * @return string
	 */
	public function getPath()
	{
		return $this->path;
	}

	/**
	 * Returns file size
	 * @return integer
	 */
	public function getSize()
	{
		return $this->size;
	}

	/**
	 * Returns file name with extension
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Returns file system directory
	 * @return string
	 */
	public function getDirectory()
	{
		return $this->directory;
	}

	/**
	 * Returns file extension
	 * @return string
	 */
	public function getExtension()
	{
		return $this->extension;
	}

	/**
	 * Returns image width
	 * @return integer
	 */
	public function getWidth()
	{
		return $this->width;
	}

	/**
	 * Returns image height
	 * @return integer 
	 */
	public function getHeight()
	{
		return $this->height;
	}

	/**
	 * Returns on of IMAGETYPE_XXX constants
	 * @return integer 
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * Returns file mime
	 * @return string 
	 */
	public function getMime()
	{
		return $this->mime;
	}

	/**
	 * Returns image channels 
	 * @return integer
	 */
	public function getChannels()
	{
		return $this->channels;
	}

	/**
	 * Returns image bits
	 * @return integer
	 */
	public function getBits()
	{
		return $this->bits;
	}

	/**
	 * Has error while processed image
	 * @return boolean 
	 */
	public function hasError()
	{
		return ! empty($this->error);
	}

	/**
	 * Returns error message
	 * @return string
	 */
	public function getError()
	{
		return $this->error;
	}

}
