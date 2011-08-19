<?php

namespace Supra\Cms\Exception;

use Supra\Exception\LocalizedException;

/**
 * CMS localized exception
 */
class CmsException extends \RuntimeException implements LocalizedException
{
	private $messageKey;
	
	/**
	 * @param string $messageKey
	 */
	public function __construct($messageKey, $message = null)
	{
		// Send message or messageKey as the exception message
		if (is_null($message)) {
			$message = $messageKey;
		}
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
