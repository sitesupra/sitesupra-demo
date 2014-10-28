<?php

namespace Supra\Package\Cms\Pages\Html;

use Supra\Package\Cms\Html\HtmlTag;

class ImageTag extends HtmlTag
{
	/**
	 * @var Image
	 */
	protected $image;

	/**
	 * @var int image width in pixels
	 */
	protected $width;

	/**
	 * @var int image height in pixels
	 */
	protected $height;

	public function __construct(Image $image)
	{
		parent::__construct('img');
		$this->image = $image;
	}

	/**
	 * @param int $width
	 */
	public function setWidth($width)
	{
		$this->width = $width;
		$this->setAttribute('width', $width);

		return $this;
	}

	/**
	 * @param int $height
	 */
	public function setHeight($height)
	{
		$this->height = $height;
		$this->setAttribute('height', $height);

		return $this;
	}

	/**
	 * Gets the image path.
	 * Pass the $width and/or $height to get the resized image path.
	 *
	 * @return string
	 */
	public function getPath($width = null, $height = null)
	{
		return $this->getResizedImagePath($width, $height);
	}

	/**
	 * {@inheritDoc}
	 */
	public function toHtml()
	{
		$this->setAttribute('src', $this->getResizedImagePath($this->width, $this->height));
	}

	/**
	 * @param int $width
	 * @param int $height
	 * @return string
	 */
	private function getResizedImagePath($width, $height)
	{
		// 1. get the filestorage
		// 2. get the resized image path for specified $width and $height.
		return '/fixme.jpg';
	}
}
