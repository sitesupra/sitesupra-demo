<?php

/*
 * Copyright (C) SiteSupra SIA, Riga, Latvia, 2015
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 */

namespace Supra\Package\Cms\Entity\ReferencedElement;

/**
 * @Entity
 */
class ImageReferencedElement extends ReferencedElementAbstract
{

	const TYPE_ID = 'image';
	const STYLE_LIGHTBOX = 'lightbox';

	/**
	 * @Column(type="supraId20")
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
	 * @Column(type="string", nullable=true)
	 * @var string
	 */
	protected $alternateText;

	/**
	 *
	 * @Column(type="string", nullable=true)
	 * @var string
	 */
	protected $title;

	/**
	 * @Column(type="text", nullable=true)
	 * @var string
	 */
	protected $description;

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
	public function getAlternateText()
	{
		return $this->alternateText;
	}

	/**
	 * @param string $alternateText
	 */
	public function setAlternateText($alternateText)
	{
		$this->alternateText = $alternateText;
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
	 * @return string
	 */
	public function getDescription()
	{
		return $this->description;
	}

	/**
	 * @param string $description
	 */
	public function setDescription($description)
	{
		$this->description = $description;
	}

	/**
	 * {@inheritdoc}
	 * @return array
	 */
	public function toArray()
	{
		return array(
			'type' 			=> self::TYPE_ID,
			'id' 			=> $this->imageId,
			'align' 		=> $this->align,
			'style' 		=> $this->style,

			'alternate_text'	=> $this->alternateText,
			'title' 		=> $this->title,
			'description' 	=> $this->description,

			'size_width' 	=> $this->width,
			'size_height' 	=> $this->height,
			'crop_top' 		=> $this->cropTop,
			'crop_left' 	=> $this->cropLeft,
			'crop_width' 	=> $this->cropWidth,
			'crop_height' 	=> $this->cropHeight,

			'size_name' 	=> $this->sizeName,
		);
	}

	/**
	 * {@inheritdoc}
	 * @param array $array
	 */
	public function fillFromArray(array $data)
	{
		$data = $data + array(
				'style' 		=> null,
				'align' 		=> null,
				'alternate_text' 	=> null,
				'title' 		=> null,
				'description' 	=> null,

				'size_width' 	=> null,
				'size_name' 	=> null,
				'crop_top' 		=> null,
				'crop_left' 	=> null,
				'crop_width' 	=> null,
				'crop_height' 	=> null,

				'size_height' 	=> null,
			);

		$this->imageId = $data['id'];
		$this->align = $data['align'];
		$this->style = $data['style'];
		$this->alternateText = $data['alternate_text'];
		$this->title = $data['title'];
		$this->description = $data['description'];

		$this->sizeName = $data['size_name'];

		$this->setWidth($data['size_width']);
		$this->setHeight($data['size_height']);
		$this->setCropTop($data['crop_top']);
		$this->setCropLeft($data['crop_left']);
		$this->setCropWidth($data['crop_width']);
		$this->setCropHeight($data['crop_height']);
	}

	/**
	 * @return bool
	 */
	public function isCropped()
	{
		return $this->getCropHeight() || $this->getCropWidth() || $this->getCropTop() || $this->getCropLeft();
	}

}
