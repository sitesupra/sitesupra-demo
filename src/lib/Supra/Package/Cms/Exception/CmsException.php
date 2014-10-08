<?php

namespace Supra\Package\Cms\Exception;

use Supra\Exception\LocalizedException;

/**
 * CMS localized exception
 */
class CmsException extends \RuntimeException implements LocalizedException
{
	
	private $messageKey;
	
	/**
	 * @param string $messageKey
	 * @param string $message
	 * @param \Exception $parentException
	 */
	public function __construct($messageKey, $message = null, \Exception $parentException = null)
	{
		// Send message or messageKey as the exception message
		if (is_null($message)) {
			$message = $messageKey;
		}
		parent::__construct($message, null, $parentException);
		
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
