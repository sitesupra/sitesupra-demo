<?php

namespace Supra\BannerMachine\Entity;

use Supra\FileStorage\Entity\File;
use Supra\ObjectRepository\ObjectRepository;

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

		$lm = ObjectRepository::getLocaleManager($this);

		$this->title = $file->getTitle($lm->getCurrent()->getId());
	}

	/**
	 * @return string
	 */
	public function getExternalPath()
	{
		$fs = ObjectRepository::getFileStorage($this);

		return $fs->getWebPath($this->file);
	}

}

