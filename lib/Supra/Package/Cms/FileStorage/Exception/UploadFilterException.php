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

namespace Supra\Package\Cms\FileStorage\Exception;

use Supra\Package\Cms\Exception\LocalizedException;

/**
 * Thrown on upload filter exception
 */
class UploadFilterException extends RuntimeException implements LocalizedException
{
	/**
	 * CMS localization error message
	 * @var string
	 */
	private $messageKey;
	
	/**
	 * @param string $messageKey
	 * @param string $message 
	 */
	public function __construct($messageKey, $message = null)
	{
		parent::__construct($message);
		$this->setMessageKey($messageKey);
	}
	
	/**
	 * @return string
	 */
	public function getMessageKey()
	{
		return $this->messageKey;
	}

	/**
	 * @param string $messageKey
	 */
	public function setMessageKey($messageKey)
	{
		$this->messageKey = $messageKey;
	}
}
