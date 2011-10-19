<?php

namespace Supra\Tests\ObjectRepository\Mockup;

/**
 * Object repository with state recover feature
 */
class ObjectRepository extends \Supra\ObjectRepository\ObjectRepository
{
	protected static $state;
	
	public static function saveCurrentState()
	{
		self::$state = self::dumpState();
	}
	
	public static function restoreCurrentState()
	{
		list(self::$callerHierarchy,
			self::$controllerStack,
			self::$objectBindings) = self::$state;
	}
	
	public function removeObject($caller, $interface)
	{
		unset(self::$objectBindings[$interface][$caller]);
	}
	
	public static function dumpState()
	{
		return array(
			self::$callerHierarchy,
			self::$controllerStack,
			self::$objectBindings
		);
	}
}
