<?php

namespace Supra\Controller\Pages\Entity\ReferencedElement;

use Supra\FileStorage\Entity\Image;

/**
 * @Entity
 */
class ImageReferencedElement extends ReferencedElementAbstract
{
	/**
	 * Image ID to keep link data without existant real image.
	 * SQL naming for CMS usage, should be fixed (FIXME).
	 * @Column(type="string", nullable="true")
	 * @var string
	 */
	protected $image;
	
	/**
	 * @Column(type="string", nullable="true")
	 * @var string
	 */
	protected $align;
	
	/**
	 * @Column(type="string", nullable="true")
	 * @var string
	 */
	protected $style;
	
	/**
	 * @Column(type="integer")
	 * @var integer
	 */
	protected $width;
	
	/**
	 * @Column(type="integer")
	 * @var integer
	 */
	protected $height;
	
	/**
	 * @Column(type="string", nullable="true")
	 * @var string
	 */
	protected $alternativeText;
	
	/**
	 * @return string
	 */
	public function getImageId()
	{
		return $this->image;
	}

	/**
	 * @param string $imageId
	 */
	public function setImageId($imageId)
	{
		$this->image = $imageId;
	}

	/**
	 * @return string
	 */
	public function getAlign()
	{
		return $this->align;
	}

	/**
	 * @param string $align
	 */
	public function setAlign($align)
	{
		$this->align = $align;
	}

	/**
	 * @return string
	 */
	public function getStyle()
	{
		return $this->style;
	}

	/**
	 * @param string $align
	 */
	public function setStyle($style)
	{
		$this->style = $style;
	}

	/**
	 * @return integer
	 */
	public function getWidth()
	{
		return $this->width;
	}

	/**
	 * @param integer $align
	 */
	public function setWidth($width)
	{
		$this->width = $width;
	}

	/**
	 * @return integer
	 */
	public function getHeight()
	{
		return $this->height;
	}

	/**
	 * @param integer $align
	 */
	public function setHeight($height)
	{
		$this->height = $height;
	}

	/**
	 * @return string
	 */
	public function getAlternativeText()
	{
		return $this->alternativeText;
	}

	/**
	 * @param string $align
	 */
	public function setAlternativeText($alternativeText)
	{
		$this->alternativeText = $alternativeText;
	}

}
