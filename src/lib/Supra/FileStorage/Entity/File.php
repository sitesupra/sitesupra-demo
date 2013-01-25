<?php

namespace Supra\FileStorage\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Supra\NestedSet;

/**
 * File object
 * @Entity
 * @Table(name="file")
 */
class File extends Abstraction\File implements NestedSet\Node\NodeLeafInterface
{
	/**
	 * {@inheritdoc}
	 */
	const TYPE_ID = 3;
	
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
	

	public function __construct()
	{
		parent::__construct();
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
	 * Gets file extension
	 * @return string
	 */
	public function getExtension()
	{
		$extension = pathinfo($this->fileName, PATHINFO_EXTENSION);

		return $extension;
	}
	
	/**
	 * Returns file name without extension
	 * @return string
	 */
	public function getFileNameWithoutExtension()
	{
		$fileName = pathinfo($this->fileName, PATHINFO_FILENAME);
		
		return $fileName;
	}
	
	/**
	 * {@inheritdoc}
	 * @param string $locale
	 * @return array
	 */
	public function getInfo($locale = null)
	{
		$info = parent::getInfo($locale);
		
		$info = $info + array(
			// FIXME: returns different type depending on the input (string, array)
			'size' => $this->getSize()
		);
		
		return $info;
	}

}
