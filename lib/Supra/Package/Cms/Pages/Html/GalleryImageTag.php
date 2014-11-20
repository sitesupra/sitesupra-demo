<?php

namespace Supra\Package\Cms\Pages\Html;

/**
 * @FIXME: not implemented.
 */
class GalleryImageTag extends ImageTag
{
	/**
	 * @var string
	 */
	protected $title;

	/**
	 * @var string
	 */
	protected $description;

	/**
	 * @param Image $image
	 * @param string $title
	 * @param string $description
	 */
	public function __construct(Image $image, $title = null, $description = null)
	{
		parent::__construct($image);
		
		$this->title = $title;
		$this->description = $description;
	}

	/**
	 * @return string
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * @return string
	 */
	public function getDescription()
	{
		return $this->description;
	}
	
}
