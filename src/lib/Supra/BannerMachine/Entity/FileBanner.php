<?php

namespace Supra\BannerMachine\Entity;

use Supra\FileStorage\Entity\File;

abstract class FileBanner extends Banner
{

	/**
	 * @ManyToOne(targetEntity="Supra\FileStorage\Entity\File")
	 * @JoinColumn(name="fileId", referencedColumnName="id")
	 * @var File
	 */
	protected $file;

	/**
	 * @return File
	 */
	public function getFile()
	{
		return $this->file;
	}

	/**
	 * @param File $file 
	 */
	public function setFile(File $file)
	{
		$this->file = $file;
	}

}

