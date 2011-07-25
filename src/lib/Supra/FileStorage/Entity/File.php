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

}
