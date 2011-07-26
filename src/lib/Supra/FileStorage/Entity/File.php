<?php

namespace Supra\FileStorage\Entity;

use Doctrine\Common\Collections\ArrayCollection,
		Doctrine\Common\Collections\Collection;

/**
 * File object
 * @Entity(repositoryClass="Supra\FileStorage\Repository\FileRepository")
 * @Table(name="file")
 */
class File extends Abstraction\File
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
	
	public function __construct() {
		parent::__construct();
		$this->metaData = new ArrayCollection();
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
	 * Get localised description
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
	
	public function isMimeTypeImage($mimetype)
	{
		$image = strpos($mimetype, 'image/');

		if ($image === 0) {
			return true;
		} else {
			return false;
		}
	}
}
