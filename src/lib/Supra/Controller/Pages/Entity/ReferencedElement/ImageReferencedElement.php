<?php

namespace Supra\Controller\Pages\Entity\ReferencedElement;

use Supra\FileStorage\Entity\Image;

/**
 * @Entity
 */
class ImageReferencedElement extends ReferencedElementAbstract
{
	const TYPE_ID = 'image';
	
	/**
	 * Image ID to keep link data without existant real image.
	 * SQL naming for CMS usage, should be fixed (FIXME).
	 * @Column(type="supraId20", nullable=true)
	 * @var string
	 */
	protected $imageId;
	
	/**
	 * @Column(type="string", nullable=true)
	 * @var string
	 */
	protected $align;
	
	/**
	 * @Column(type="string", nullable=true)
	 * @var string
	 */
	protected $style;
	
	/**
	 * @Column(type="string", nullable=true)
	 * @var string
	 */
	protected $sizeName;
	
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
	 * 
	 * @Column(type="string", nullable=true)
	 * @var string
	 */
	protected $title;
	
	/**
	 * @Column(type="string", nullable=true)
	 * @var string
	 */
	protected $alternativeText;
	
	/**
	 * @return string
	 */
	public function getImageId()
	{
		return $this->imageId;
	}

	/**
	 * @param string $imageId
	 */
	public function setImageId($imageId)
	{
		$this->imageId = $imageId;
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
	 * @param string $style
	 */
	public function setStyle($style)
	{
		$this->style = $style;
	}
	
	/**
	 * @return string
	 */
	public function getSizeName()
	{
		return $this->sizeName;
	}

	/**
	 * @param string $sizeName
	 */
	public function setSizeName($sizeName)
	{
		$this->sizeName = $sizeName;
	}

	/**
	 * @return integer
	 */
	public function getWidth()
	{
		return $this->width;
	}

	/**
	 * @param integer $width
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
	 * @param integer $height
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
	 * @param string $alternativeText
	 */
	public function setAlternativeText($alternativeText)
	{
		$this->alternativeText = $alternativeText;
	}
	
	/**
	 * @return string 
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * @param string $title 
	 */
	public function setTitle($title)
	{
		$this->title = $title;
	}

		/**
	 * {@inheritdoc}
	 * @return array
	 */
	public function toArray()
	{
		$array = array(
			'type' => self::TYPE_ID,
			'image' => $this->imageId,
			'align' => $this->align,
			'style' => $this->style,
			'size_width' => $this->width,
			'size_height' => $this->height,
			'title' => $this->title,
			'description' => $this->alternativeText,
			'size_name' => $this->sizeName,
		);
		
		return $array;
	}
	
	/**
	 * {@inheritdoc}
	 * @param array $array
	 */
	public function fillArray(array $array)
	{
		$this->imageId = $array['image'];
		$this->align = $array['align'];
		$this->style = $array['style'];
		$this->width = $array['size_width'];
		$this->height = $array['size_height'];
		$this->title = $array['title'];
		$this->alternativeText = $array['description'];
		$this->sizeName = $array['size_name'];
	}
	
//	public function getOwner()
//	{
//		
//	}

}
