<?php

namespace Supra\Package\Cms\Entity;

/**
 * Image object
 * @Entity
 * @Table(name="image")
 */
class Image extends File
{
	/**
	 * {@inheritdoc}
	 */
	const TYPE_ID = 2;
	
	/**
	 * @Column(type="integer", nullable=false)
	 * @var integer
	 */
	protected $width;

	/**
	 * @Column(type="integer", nullable=false)
	 * @var integer
	 */
	protected $height;
	
	/**
	 * @OneToMany(targetEntity="ImageSize", mappedBy="master", cascade={"persist", "remove"}, indexBy="name")
	 * @var Collection
	 */
	protected $imageSizes;

	/**
	 * Set width
	 * 
	 * @param integer $width 
	 */
	public function setWidth($width) 
	{
		$newWidth = intval($width);
		if ($newWidth > 0) {
			$this->width = $newWidth;
		}
	}

	/**
	 * Set height
	 * 
	 * @param type $height 
	 */
	public function setHeight($height)
	{
		$newHeight = intval($height);
		if ($newHeight > 0) {
			$this->height = $newHeight;
		}
	}

	/**
	 * Get width
	 *
	 * @return integer
	 */
	public function getWidth()
	{
		return $this->width;
	}

	/**
	 * Get height
	 *
	 * @return integer
	 */
	public function getHeight()
	{
		return $this->height;
	}

	/**
	 * Set (add) image size
	 *
	 * @param ImageSize $size
	 * @return boolean
	 */
	public function addImageSize(ImageSize $size)
	{
		if ($this->imageSizes->containsKey($size->getName())) {
			return false;
		} else {
			$this->imageSizes->set($size->getName(), $size);
			return true;
		}
	}

	/**
	 * Find image size data
	 *
	 * @param string $sizeName
 	 * @return ImageSize
	 */
	public function findImageSize($sizeName)
	{
		if ($this->imageSizes->containsKey($sizeName)) {
			return $this->imageSizes->get($sizeName);
		} else {
			return null;
		}
	}

	/**
	 * Find image size data or create new if not found
	 *
	 * @param string $sizeName
	 * @return ImageSize
	 */
	public function getImageSize($sizeName)
	{
		$size = $this->findImageSize($sizeName);
		if ( ! $size instanceof ImageSize) {
			$size = new ImageSize($sizeName);
			$size->setMaster($this);
		}
		return $size;
	}

	/**
	 * Get collection of all assigned sizes
	 *
	 * @return \Doctrine\Common\Collections\ArrayCollection
	 */
	public function getImageSizeCollection()
	{
		return $this->imageSizes;
	}
	
	/**
	 * {@inheritdoc}
	 * @param string $locale
	 * @return array
	 */
	public function getInfo($locale = null)
	{
		$info = parent::getInfo($locale);
		
		$info['sizes'] = array();
		$sizes = $this->getImageSizeCollection();

		foreach ($sizes as $size) {
			$sizeName = $size->getName();
			$info['sizes'][$sizeName] = array(
				'id' => $sizeName,
				'width' => $size->getWidth(),
				'height' => $size->getHeight(),
			);
		}

		$info['sizes']['original'] = array(
			'id' => 'original',
			'width' => $this->getWidth(),
			'height' => $this->getHeight(),
		);
		
		return $info;
	}
}
