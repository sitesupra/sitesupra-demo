<?php

namespace Supra\FileStorage\Entity;

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
	public function setSize(int $fileSize) 
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

}
