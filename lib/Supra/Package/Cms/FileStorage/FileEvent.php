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

namespace Supra\Package\Cms\FileStorage;

use Supra\Package\Cms\Entity\Abstraction\File;
use Symfony\Component\EventDispatcher\Event;

class FileEvent extends Event
{
	const FILE_EVENT_FILE_SELECTED = 'fileSelected';
	const FILE_EVENT_PRE_DELETE = 'preFileDelete';
	const FILE_EVENT_POST_DELETE = 'postFileDelete';
	const FILE_EVENT_PRE_RENAME = 'preFileRename';
	const FILE_EVENT_POST_RENAME = 'postFileRename';
	
	/**
	 * @var File
	 */
	protected $file;

	/**
	 * @param File $file 
	 */
	public function setFile(File $file)
	{
		$this->file = $file;
	}

	/**
	 * @return File
	 */
	public function getFile()
	{
		return $this->file;
	}

}

