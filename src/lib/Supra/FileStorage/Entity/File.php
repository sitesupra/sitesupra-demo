<?php

namespace Supra\FileStorage\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Supra\NestedSet;

/**
 * File object
 * @Entity(repositoryClass="Supra\FileStorage\Repository\ForbiddenRepository")
 * @Table(name="file")
 */
class File extends Abstraction\File implements NestedSet\Node\NodeLeafInterface
{

	/**
	 * @Column(type="string", name="mime_type", nullable=false)
	 * @var string
	 */
	protected $mimeType;
	/**
	 * @Column(type="integer", name="file_size", nullable=false)
	 * @var integer
	 */
	protected $fileSize;
	/**
	 * @OneToMany(targetEntity="MetaData", mappedBy="master", cascade={"persist", "remove"}, indexBy="locale")
	 * @var Collection
	 */
	protected $metaData;
	/**
	 * @Column(type="boolean", name="public")
	 * @var integer
	 */
	protected $public = true;
	/**
	 * @OneToMany(targetEntity="ImageSize", mappedBy="master", cascade={"persist", "remove"}, indexBy="name")
	 * @var Collection
	 */
	protected $imageSizes;

	public function __construct()
	{
		parent::__construct();
		$this->metaData = new ArrayCollection();
		$this->imageSizes = new ArrayCollection();
	}

	/**
	 * Set mime-type
	 *
	 * @param string $mimeType 
	 */
	public function setMimeType($mimeType)
	{
		$this->mimeType = $mimeType;
	}

	/**
	 * Get mime-type
	 *
	 * @return string
	 */
	public function getMimeType()
	{
		return $this->mimeType;
	}

	/**
	 * Set file size
	 *
	 * @param int $fileSize
	 */
	public function setSize($fileSize)
	{
		$this->fileSize = $fileSize;
	}

	/**
	 * Get file size
	 *
	 * @return int
	 */
	public function getSize()
	{
		return $this->fileSize;
	}

	/**
	 * Set meta data
	 *
	 * @param MetaData $data 
	 */
	public function setMetaData($data)
	{
		$this->addUnique($this->metaData, $data, 'locale');
	}

	/**
	 * Gets file extension
	 * @return string
	 */
	public function getExtension()
	{
		$fileinfo = pathinfo($this->getName());
		$extension = $fileinfo['extension'];

		return $extension;
	}

	/**
	 * Get meta-data for locale
	 *
	 * @param string $locale
	 * @return \Supra\FileStorage\Entity\MetaData
	 */
	public function getMetaData($locale = null)
	{
		if (empty($locale)) {
			// FIXME replace with current locale
			$locale = 'en';
		}
		if ($this->metaData->containsKey($locale)) {
			return $this->metaData->get($locale);
		} else {
			return null;
		}
	}

	/**
	 * Get localized title
	 *
	 * @param string $locale
	 * @return string
	 */
	public function getTitle($locale = null)
	{
		$metaData = $this->getMetaData($locale);
		if ($metaData instanceof \Supra\FileStorage\Entity\MetaData) {
			return $metaData->getTitle();
		} else {
			return $this->getName();
		}
	}

	/**
	 * Get localized description
	 *
	 * @param string $locale
	 * @return string
	 */
	public function getDescription($locale = null)
	{
		$metaData = $this->getMetaData($locale);
		if ($metaData instanceof \Supra\FileStorage\Entity\MetaData) {
			return $metaData->getDescription();
		} else {
			return $this->getName();
		}
	}

	public function isMimeTypeImage($mimetype = null)
	{
		if (empty($mimetype) && isset($this->mimeType)) {
			$mimetype = $this->getMimeType();
		}
		
		$image = strpos($mimetype, 'image/');

		if ($image === 0) {
			return true;
		} else {
			return false;
		}
	}

	public function isPublic()
	{
		return $this->public;
	}

	public function setPublic($public)
	{
		$this->public = $public;
	}

	/**
	 * Set (add) image size
	 *
	 * @param ImageSize $size
	 * @return boolean
	 */
	public function setImageSize($size)
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
	 * Remove image size
	 * 
	 * @param type $title
	 */
	public function removeImageSize($sizeName)
	{
		$size = $this->getImageSize($sizeName);
		$this->getConnection()->remove($sizeName);
	}

	/**
	 * Get collection of all assigned sizes
	 *
	 * @return type 
	 */
	public function getImageSizeCollection()
	{
		return $this->imageSizes;
	}

}
