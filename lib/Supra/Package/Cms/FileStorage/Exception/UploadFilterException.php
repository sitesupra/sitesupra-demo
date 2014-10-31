<?php

namespace Supra\Package\Cms\FileStorage\Exception;

use Supra\Exception\LocalizedException;

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
