<?php

namespace Supra\Exception;

/**
 * CMS exception with localization feature
 */
interface LocalizedException
{
	/**
	 * @param string $messageKey
	 */
	public function setMessageKey($messageKey);
	
	/**
	 * @returns string
	 */
	public function getMessageKey();
}
