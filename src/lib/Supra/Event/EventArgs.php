<?php

namespace Supra\Event;

/**
 * Event argument class
 */
class EventArgs
{
	/**
	 * Shared empty arguments class
	 * @var EventArgs
	 */
	private static $emptyInstance;
	
	/**
	 * @return EventArgs
	 */
	public static function getEmptyInstance()
	{
		if (is_null(self::$emptyInstance)) {
			self::$emptyInstance = new self();
		}
		
		return self::$emptyInstance;
	}
}
