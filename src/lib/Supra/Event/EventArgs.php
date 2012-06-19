<?php

namespace Supra\Event;

use Supra\ObjectRepository\ObjectRepository;

/**
 * Event argument class
 */
class EventArgs
{
	private $caller;
	
	/**
	 * Must pass the caller object, classname, namespace so called events can be filtered
	 * @param mixed $caller
	 */
	public function __construct($caller = null)
	{
		if (is_null($caller)) {
			$caller = ObjectRepository::DEFAULT_KEY;
		}
		
		$this->caller = $caller;
	}

	/**
	 * @return mixed
	 */
	public function getCaller()
	{
		return $this->caller;
	}

	/**
	 * Set different caller
	 * @param mixed $caller
	 */
	public function setCaller($caller)
	{
		$this->caller = $caller;
	}

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
