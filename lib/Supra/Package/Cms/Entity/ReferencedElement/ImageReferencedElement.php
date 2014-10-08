<?php

namespace Supra\Package\Cms\Entity\ReferencedElement;

use Supra\FileStorage\Entity\Image;

/**
 * @Entity
 */
class ImageReferencedElement extends ReferencedElementAbstract
{

	const TYPE_ID = 'image';

	const STYLE_LIGHTBOX = 'lightbox';

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
	 * @Column(type="integer", nullable=true)
	 * @var integer
	 */
	protected $width;

	/**
	 * @Column(type="integer", nullable=true)
	 * @var integer
	 */
	protected $height;

	/**
	 * @Column(type="integer", nullable=true)
	 * @var integer
	 */
	protected $cropLeft;

	/**
	 * @Column(type="integer", nullable=true)
	 * @var integer
	 */
	protected $cropTop;

	/**
	 * @Column(type="integer", nullable=true)
	 * @var integer
	 */
	protected $cropWidth;

	/**
	 * @Column(type="integer", nullable=true)
	 * @var integer
	 */
	protected $cropHeight;

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
		$width = (int) $width;

		if ($width < 0) {
			throw new \InvalidArgumentException("Negative width '$width' received");
		} elseif ($width == 0) {
			$width = null;
		}

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
		$height = (int) $height;

		if ($height < 0) {
			throw new \InvalidArgumentException("Negative height '$height' received");
		} elseif ($height == 0) {
			$height = null;
		}

		$this->height = $height;
	}

	/**
	 * @return integer
	 */
	public function getCropLeft()
	{
		return $this->cropLeft;
	}

	/**
	 * @param integer $cropLeft
	 * @throws \InvalidArgumentException
	 */
	public function setCropLeft($cropLeft)
	{
		$cropLeft = (int) $cropLeft;

		if ($cropLeft < 0) {
			throw new \InvalidArgumentException("Negative crop left '$cropLeft' received");
		} elseif ($cropLeft == 0) {
			$cropLeft = null;
		}

		$this->cropLeft = $cropLeft;
	}

	/**
	 * @return integer
	 */
	public function getCropTop()
	{
		return $this->cropTop;
	}

	/**
	 * @param integer $cropTop
	 * @throws \InvalidArgumentException
	 */
	public function setCropTop($cropTop)
	{
		$cropTop = (int) $cropTop;

		if ($cropTop < 0) {
			throw new \InvalidArgumentException("Negative crop top '$cropTop' received");
		} elseif ($cropTop == 0) {
			$cropTop = null;
		}

		$this->cropTop = $cropTop;
	}

	/**
	 * @return integer
	 */
	public function getCropWidth()
	{
		return $this->cropWidth;
	}

	/**
	 * @param integer $cropWidth
	 * @throws \InvalidArgumentException
	 */
	public function setCropWidth($cropWidth)
	{
		$cropWidth = (int) $cropWidth;

		if ($cropWidth < 0) {
			throw new \InvalidArgumentException("Negative crop width '$cropWidth' received");
		} elseif ($cropWidth == 0) {
			$cropWidth = null;
		}

		$this->cropWidth = $cropWidth;
	}

	/**
	 * @return integer
	 */
	public function getCropHeight()
	{
		return $this->cropHeight;
	}

	/**
	 * @param integer $cropHeight
	 * @throws \InvalidArgumentException
	 */
	public function setCropHeight($cropHeight)
	{
		$cropHeight = (int) $cropHeight;

		if ($cropHeight < 0) {
			throw new \InvalidArgumentException("Negative crop height '$cropHeight' received");
		} elseif ($cropHeight == 0) {
			$cropHeight = null;
		}
		$this->cropHeight = $cropHeight;
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
			'id' => $this->imageId,
//			//TODO: Remove after JS change (#5686)
//			'image' => $this->imageId,
			'align' => $this->align,
			'style' => $this->style,
			'size_width' => $this->width,
			'size_height' => $this->height,
			'crop_top' => $this->cropTop,
			'crop_left' => $this->cropLeft,
			'crop_width' => $this->cropWidth,
			'crop_height' => $this->cropHeight,
			'title' => $this->title,
			'description' => $this->alternativeText,
			'size_name' => $this->sizeName,
			'imageId' => $this->imageId,
		);

		return $array;
	}

	/**
	 * {@inheritdoc}
	 * @param array $array
	 */
	public function fillArray(array $array)
	{
		$array = $array + array(
			'align' => null,
			'style' => null,
			'size_width' => null,
			'size_height' => null,
			'crop_top' => null,
			'crop_left' => null,
			'crop_width' => null,
			'crop_height' => null,
			'title' => null,
			'description' => null,
			'size_name' => null,
		);

		// TODO: should be removed after JS changes (#5686)
		if (empty($array['id']) && ! empty($array['image'])) {
			$array['id'] = $array['image'];
		}

		$this->imageId = $array['id'];
		$this->align = $array['align'];
		$this->style = $array['style'];
		$this->setWidth($array['size_width']);
		$this->setHeight($array['size_height']);
		$this->setCropTop($array['crop_top']);
		$this->setCropLeft($array['crop_left']);
		$this->setCropWidth($array['crop_width']);
		$this->setCropHeight($array['crop_height']);
		$this->title = $array['title'];
		$this->alternativeText = $array['description'];
		$this->sizeName = $array['size_name'];
	}

	/**
	 *
	 */
	public function isCropped()
	{
		return $this->getCropHeight() || $this->getCropWidth() || $this->getCropTop() || $this->getCropLeft();
	}

}
