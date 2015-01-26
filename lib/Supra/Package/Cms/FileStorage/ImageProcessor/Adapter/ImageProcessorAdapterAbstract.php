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

namespace Supra\Package\Cms\FileStorage\ImageProcessor\Adapter;

use Supra\Package\Cms\FileStorage\Exception\ImageProcessorException;
use Supra\Package\Cms\FileStorage\ImageInfo;
use Supra\Package\Cms\FileStorage\FileStorage;

abstract class ImageProcessorAdapterAbstract implements ImageProcessorAdapterInterface
{
	/**
	 * @var FileStorage
	 */
	protected $fileStorage;

	/**
	 * @param FileStorage $fileStorage
	 */
	public function setFileStorage(FileStorage $fileStorage)
	{
		$this->fileStorage = $fileStorage;
	}

	/**
	 * @return FileStorage
	 */
	public function getFileStorage()
	{
		return $this->fileStorage;
	}

	/**
	 * @param string $fileName
	 * @return ImageInfo
	 * @throws ImageProcessorException
	 */
	protected function getImageInfo($fileName)
	{
		$info = new ImageInfo($fileName);

		if ($info->hasError()) {
			throw new ImageProcessorException('File ' . $fileName . ' not found or is not readable. ' . $info->getError());
		}

		return $info;
	}
}