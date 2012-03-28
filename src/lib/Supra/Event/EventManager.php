<?php

namespace Supra\Event;

use Supra\Log\Log;
use Supra\Loader\Loader;
use Supra\ObjectRepository\ObjectRepository;

/**
 * Event manager object
 */
class EventManager
{
	/**
	 * Shared empty arguments class
	 * @var EventArgs
	 */
	private static $emptyInstance;
	
	/**
	 * Listeners
	 * @var array
	 */
	protected $listeners = array();
	
	/**
	 * @return EventManager
	 */
	public static function getEmptyInstance()
	{
		if (is_null(self::$emptyInstance)) {
			self::$emptyInstance = new self();
		}
		
		return self::$emptyInstance;
	}
	
	/**
	 * Add listener
	 * @param string|array $eventType
	 * @param callback|object|\Closure $listener
	 * @param mixed $bindingFilter
	 */
	public function listen($eventTypes, $listener, $bindingFilter = null)
	{
		// Do nothing for empty instance
		if ($this === self::$emptyInstance) {
			return;
		}
		
		$eventTypes = (array) $eventTypes;
		
		if ( ! is_null($bindingFilter)) {
			$bindingFilter = ObjectRepository::normalizeCallerArgument($bindingFilter);
		} else {
			$bindingFilter = ObjectRepository::DEFAULT_KEY;
		}
		
		foreach ($eventTypes as $eventType) {

			if ( ! isset($this->listeners[$eventType])) {
				$this->listeners[$eventType] = array();
			}
			
			// don't validate callback now for performance reasons
			//TODO: could add validation for development environment
			$this->listeners[$eventType][] = array(
				$listener,
				$bindingFilter,
			);
		}
	}

	/**
	 * Fires event
	 * @param string $eventType
	 * @param EventArgs $eventArgs
	 * @throws Exception\InvalidListenerObject if listener found is not valid
	 */
	public function fire($eventType, EventArgs $eventArgs = null)
	{
		// Do nothing for empty instance
		if ($this === self::$emptyInstance) {
			return;
		}
		
		// Will pass empty object on absence
		if (is_null($eventArgs)) {
			$eventArgs = EventArgs::getEmptyInstance();
		}
		
		$caller = $eventArgs->getCaller();
		
		if ( ! empty($this->listeners[$eventType])) {
			foreach ($this->listeners[$eventType] as $listenerData) {
				
				$listener = $listenerData[0];
				$bindingFilter = $listenerData[1];
				
				// Skip if the event is not bound to the caller
				if ( ! ObjectRepository::isParentCaller($caller, $bindingFilter)) {
					continue;
				}
				
				if ($listener instanceof \Closure) {
					$listener($eventArgs);
				} elseif (is_callable($listener)) {
					call_user_func($listener, $eventArgs);
				} elseif (is_object($listener)) {
					$listener->$eventType($eventArgs);
					
				//TODO: This is bad because the listener will be separate instance 
				// at all times. Should incubate inside some object maybe.
//				} elseif (is_string($listener)) {
//					$listener = Loader::getClassInstance($listener);
//					$listener->$eventType($eventArgs);
				} else {
					Log::warn("Listener event type $eventType is not recognized as callable: ", $listener);
					
					throw new Exception\InvalidListenerObject("Listener event type $eventType is not recognized as callable argument");
				}
			}
		}
	}

	/**
	 * Remove listeners for object or class, specific event type or everything
	 * @param array|string $eventTypes
	 */
	public function removeListeners($eventTypes = null)
	{
		$eventTypes = (array) $eventTypes;
		
		foreach ($eventTypes as $eventType) {

			// Remove all listeners if no specific event type provided
			if (is_null($eventType)) {
				$this->listeners = array();
				
			// Unset the event from firing
			} else {
				unset($this->listeners[$eventType]);
			}
		}
	}
	
	/**
	 * Removes specific listener. 
	 * @param string|array $eventType
	 * @param callback|object|\Closure $listener
	 * @return boolean true if listener found for all event types specified
	 */
	public function removeListener($eventTypes, $listener)
	{
		$eventTypes = (array) $eventTypes;
		$removed = true;
		
		foreach ($eventTypes as $eventType) {
		
			// No subscription to this event type at all, skip
			if (empty($this->listeners[$eventType])) {
				$removed = false;
				continue;
			}

			// Do the search in the reverse order, will remove last added
			// listener if the same listener is added multiple times
			$keys = array_reverse(array_keys($this->listeners[$eventType]));

			foreach ($keys as $key) {
				if ($this->listeners[$eventType][$key][0] === $listener) {
					unset($this->listeners[$eventType][$key]);
					
					// Check the next event type
					continue 2;
				}
			}
			
			$removed = false;
		}
		
		return $removed;
	}
}
