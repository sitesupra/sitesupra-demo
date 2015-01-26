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

namespace Supra\Package\Cms\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Supra\Core\NestedSet\Node\NodeLeafInterface;
use Supra\NestedSet;

/**
 * File object
 * @Entity
 * @Table(name="file")
 */
class File extends Abstraction\File implements NodeLeafInterface
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
	 * {@inheritDoc}
	 * Additionally, checks if extension has changed
	 * 
	 * @throws \RuntimeException
	 */
	public function setFileName($fileName)
	{
		$hasFilename = ($this->fileName !== null);
		
		if ($hasFilename) {
			$previousName = $this->fileName;
			$previousExtension = $this->getExtension();
		}
		
		parent::setFileName($fileName);
		
		if ($hasFilename) {
			
			$newExtension = $this->getExtension();

			if (strcasecmp($previousExtension, $newExtension) !== 0) {

				// restore name back to previous one. Because that's safer.
				parent::setFileName($previousName);

				throw new \RuntimeException('Extension change is not allowed');
			}
		}
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
