<?php

namespace Supra\Event;

use Supra\Log\Log;

/**
 * Event registry object
 */
class Registry
{
	/**
	 * Singleton instance
	 * @var Registry
	 */
	protected static $instance;

	/**
	 * Listeners
	 * @var array
	 */
	protected $listeners = array();

	/**
	 * Get the singleton instance
	 * @return Registry
	 */
	public static function getInstance()
	{
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Add listener
	 * @param string|object $element
	 * @param string $eventType
	 * @param callback $callBack with arguments ($identificator, $eventType, $parameters)
	 * @throws Exception if arguments are invalid
	 */
	public static function listen($element, $eventType, $callBack)
	{
		$self = self::getInstance();
		$identificators = self::getElementIdentificators($element);
		self::validateArguments($identificators, $eventType);
		
		// Class name for string parameter, object hash for instances
		$mainIdentificator = $identificators[0];

		if ( ! isset($self->listeners[$mainIdentificator])) {
			$self->listeners[$mainIdentificator] = array();
		}
		if ( ! isset($self->listeners[$mainIdentificator][$eventType])) {
			$self->listeners[$mainIdentificator][$eventType] = array();
		}
		// don't validate callback now for performance reasons
		//TODO: could add validation for development environment
		$self->listeners[$mainIdentificator][$eventType][] = $callBack;
	}

	/**
	 * Fire event
	 * @param string|object $element
	 * @param string $eventType
	 * @param mixed $parameters
	 * @throws Exception if arguments are invalid
	 */
	public static function fire($element, $eventType, $parameters = null)
	{
		$self = self::getInstance();
		$identificators = self::getElementIdentificators($element);
		foreach ($identificators as $identificator) {
			if ( ! empty($self->listeners[$identificator][$eventType])) {
				foreach ($self->listeners[$identificator][$eventType] as $listener) {
					if ( ! is_callable($listener)) {
						Log::warn("Listener for element $identificator event type $eventType is not callable: ", $listener);
					} else {
						call_user_func($listener, $identificator, $eventType, $parameters);
					}
				}
			}
		}
	}

	/**
	 * Remove listeners for object or class, specific event type or everything
	 * @param object|string $element
	 * @param string $eventType
	 * @throws Exception if arguments are invalid
	 */
	public static function removeListener($element, $eventType = '')
	{
		$self = self::getInstance();
		$identificators = self::getElementIdentificators($element);
		self::validateArguments($identificators, $eventType);
		$mainIdentificator = $identificators[0];
		
		// Stop if no listeners for this element given
		if ( ! isset($self->listeners[$mainIdentificator])) {
			return;
		}

		// Remove all listeners if no specific event type provided
		if ($eventType === '') {
			unset($self->listeners[$mainIdentificator]);
			return;
		}

		// Stop if no listeners for this element/event type is found
		if ( ! isset($self->listeners[$mainIdentificator][$eventType])) {
			return;
		}

		unset($self->listeners[$mainIdentificator][$eventType]);
	}

	/**
	 * Get element identificator string.
	 * If oject passed return object hash and normalized object classname.
	 * If class name passed, normalize it and return as single array element.
	 * @param string|object $element
	 * @return string[]
	 */
	protected static function getElementIdentificators($element)
	{
		$identificators = array();
		if (is_object($element)) {
			$className = get_class($element);
			$identificators[] = $className . '#' . spl_object_hash($element);
		} elseif (is_string($element)) {
			$className = $element;
		} else {
			return array();
		}
		// normalize the class name
		$className = trim($className, '\\');
		$className = strtolower($className);
		$identificators[] = $className;
		return $identificators;
	}

	/**
	 * Validate parameters
	 * @param array $identificators
	 * @param string $eventType
	 * @throws Exception if any argument is not valid
	 */
	protected static function validateArguments(array $identificators, $eventType = '')
	{
		if (empty($identificators)) {
			$elementType = gettype($element);
			throw new Exception("Wrong element type $elementType provided to Evenet::listen() method");
		}
		if ( ! is_string($eventType)) {
			$type = gettype($eventType);
			throw new Exception("Wrong argument type $type for event type provided to Evenet::listen() method, string expected");
		}
	}
}