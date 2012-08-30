<?php

namespace Supra\FileStorage\Entity;

/**
 * Image resized versions
 * @Entity
 * @Table(name="image_size", uniqueConstraints={@UniqueConstraint(name="master_id_name_idx", columns={"master_id", "name"})})
 */
class ImageSize extends Abstraction\Entity
{

	/**
	 * @ManyToOne(targetEntity="Image", cascade={"persist"}, inversedBy="imageSizes")
	 * @JoinColumn(name="master_id", referencedColumnName="id", nullable=true)
	 * @var Page
	 */
	protected $master;

	/**
	 * @Column(type="integer")
	 * @var integer
	 */
	protected $width = 0;

	/**
	 * @Column(type="integer")
	 * @var integer
	 */
	protected $height = 0;

	/**
	 * @Column(type="string", nullable=false)
	 * @var string
	 */
	protected $name;

	/**
	 * @Column(type="integer")
	 * @var integer
	 */
	protected $quality = 95;

	/**
	 * @Column(name="target_width", type="integer", nullable=false)
	 * @var integer
	 */
	protected $targetWidth = 0;

	/**
	 * @Column(name="target_height", type="integer", nullable=false)
	 * @var integer
	 */
	protected $targetHeight = 0;

	/**
	 * @Column(name="crop_mode", type="boolean", nullable=false)
	 * @var boolean
	 */
	protected $cropMode = false;

	/**
	 * @Column(name="crop_top", type="integer", nullable=true)
	 * @var integer
	 */
	protected $cropTop;

	/**
	 * @Column(name="crop_left", type="integer", nullable=true)
	 * @var integer
	 */
	protected $cropLeft;

	/**
	 * @Column(name="crop_width", type="integer", nullable=true)
	 * @var integer
	 */
	protected $cropWidth;

	/**
	 * @Column(name="crop_height", type="integer", nullable=true)
	 * @var integer
	 */
	protected $cropHeight;

	/**
	 * Construct
	 * @param string $sizeName
	 * @param bool $cropped
	 */
	public function __construct($sizeName = null)
	{
		parent::__construct();
		if ( ! empty($sizeName)) {
			$this->setName($sizeName);
		}
	}

	/**
	 * Set width
	 * @param int $width 
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
	 * @param int $height 
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
	 * @return int
	 */
	public function getWidth()
	{
		return $this->width;
	}

	/**
	 * Get height
	 * @return int
	 */
	public function getHeight()
	{
		return $this->height;
	}

	/**
	 * Set quality
	 * @param int $quality 
	 */
	public function setQuality($quality)
	{
		$this->quality = intval($quality);
	}

	/**
	 * Get quality
	 * @return type Get quality
	 */
	public function getQuality()
	{
		return $this->quality;
	}

	/**
	 * Set size name 
	 * @param string $sizeName
	 */
	public function setName($sizeName)
	{
		if ( ! empty($sizeName)) {
			$this->name = $sizeName;
		}
	}

	/**
	 * Get size name
	 * @return string 
	 */
	public function getName()
	{
		return (string) $this->name;
	}

	/**
	 * Get size folder name as WxH formatted string
	 * @return string 
	 */
	public function getFolderName()
	{
		$return = array($this->getWidth(), 'x', $this->getHeight());

		if ($this->isCropped()) {

			$return[] = 't';
			$return[] = intval($this->getCropTop());
			$return[] = 'l';
			$return[] = intval($this->getCropLeft());
			$return[] = 'w';
			$return[] = intval($this->getCropWidth());
			$return[] = 'h';
			$return[] = intval($this->getCropHeight());
		}

		return join('', $return);
	}

	/**
	 * @return boolean
	 */
	public function isCropped()
	{
		return $this->getCropLeft() || $this->getCropTop() || $this->getCropWidth() || $this->getCropHeight();
	}

	/**
	 * Set master object (image)
	 * @param Image $master
	 * @return boolean
	 */
	public function setMaster(Image $master)
	{
		$attached = $master->addImageSize($this);

		if ($attached) {
			$this->master = $master;
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Get master object (image)
	 * @return Image
	 */
	public function getMaster()
	{
		return $this->master;
	}

	/**
	 * Set target size width
	 * @param integer $width
	 */
	public function setTargetWidth($width)
	{
		$newTargetWidth = intval($width);
		if ($newTargetWidth >= 0) {
			$this->targetWidth = $newTargetWidth;
		}
	}

	/**
	 * Get target size width
	 * @return integer
	 */
	public function getTargetWidth()
	{
		return $this->targetWidth;
	}

	/**
	 * Set target size height
	 * @param integer $height 
	 */
	public function setTargetHeight($height)
	{
		$newTargetHeight = intval($height);
		if ($newTargetHeight >= 0) {
			$this->targetHeight = $newTargetHeight;
		}
	}

	/**
	 * Get target size height
	 * @return integer
	 */
	public function getTargetHeight()
	{
		return $this->targetHeight;
	}

	/**
	 * Set crop mode state
	 * @param type $cropped 
	 */
	public function setCropMode($cropped)
	{
		$this->cropMode = (bool) $cropped;
	}

	/**
	 * Get crop mode
	 * @return boolean
	 */
	public function getCropMode()
	{
		return $this->cropMode;
	}

	public function getCropTop()
	{
		return $this->cropTop;
	}

	/**
	 * @param integer $cropTop
	 */
	public function setCropTop($cropTop)
	{
		$this->cropTop = $cropTop;
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
	 */
	public function setCropLeft($cropLeft)
	{
		$this->cropLeft = $cropLeft;
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
	 */
	public function setCropWidth($cropWidth)
	{
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
	 */
	public function setCropHeight($cropHeight)
	{
		$this->cropHeight = $cropHeight;
	}

}
