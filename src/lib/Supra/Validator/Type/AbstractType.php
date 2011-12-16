<?php

namespace Supra\Validator\Type;

use Supra\Validator\Exception;
use Supra\Loader\Loader;

/**
 * Abstract validation type
 */
abstract class AbstractType implements ValidationTypeInterface
{
	const BIGINT = 'bigint';
	const BOOLEAN = 'boolean';
//	const DATETIME = 'datetime';
//	const DATETIMETZ = 'datetimetz';
//	const DATE = 'date';
//	const TIME = 'time';
//	const DECIMAL = 'decimal';
	const INTEGER = 'integer';
	const SMALLINT = 'smallint';
//	const STRING = 'string';
//	const TEXT = 'text';
	const FLOAT = 'float';
	const EMAIL = 'email';
	const POST_FILE = 'post_file';
	
	/**
	 * Map of instantiated type objects
	 * @var array
	 */
	private static $typeObjects = array();

	/**
	 * The map of supported types
	 * @var array
	 */
	private static $typesMap = array(
		self::BOOLEAN => 'Supra\Validator\Type\BooleanType',
		self::INTEGER => 'Supra\Validator\Type\IntegerType',
		self::SMALLINT => 'Supra\Validator\Type\SmallIntType',
		self::BIGINT => 'Supra\Validator\Type\BigIntType',
//		self::STRING => 'Supra\Validator\Type\StringType',
//		self::TEXT => 'Supra\Validator\Type\TextType',
//		self::DATETIME => 'Supra\Validator\Type\DateTimeType',
//		self::DATETIMETZ => 'Supra\Validator\Type\DateTimeTzType',
//		self::DATE => 'Supra\Validator\Type\DateType',
//		self::TIME => 'Supra\Validator\Type\TimeType',
//		self::DECIMAL => 'Supra\Validator\Type\DecimalType',
		self::FLOAT => 'Supra\Validator\Type\FloatType',
		self::EMAIL => 'Supra\Validator\Type\EmailType',
		self::POST_FILE => 'Supra\Validator\Type\PostFileType',
	);

	/**
	 * Factory method to create type instances.
	 *
	 * @param string $name
	 * @return ValidationTypeInterface
	 * @throws Exception\UnknownValidationType
	 */
	public static function getType($name)
	{
		if ( ! isset(self::$typeObjects[$name])) {
			
			if ( ! isset(self::$typesMap[$name])) {
				throw new Exception\ValidationTypeException("Validation type $name is unknown");
			}
			
			self::$typeObjects[$name] = Loader::getClassInstance(self::$typesMap[$name], 
					ValidationTypeInterface::CN);
		}

		return self::$typeObjects[$name];
	}

	/**
	 * Adds a custom type to the type map
	 *
	 * @param string $name
	 * @param string $className
	 * @throws Exception\ValidationTypeException
	 */
	public static function addType($name, $className)
	{
		if (isset(self::$typesMap[$name])) {
			throw new Exception\ValidationTypeException("Validation type $name is already defined");
		}

		self::$typesMap[$name] = $className;
	}

	/**
	 * Checks if exists support for a type
	 *
	 * @param string $name
	 * @return boolean
	 */
	public static function hasType($name)
	{
		return isset(self::$typesMap[$name]);
	}

	/**
	 * Overrides an already defined type
	 * @param string $name
	 * @param string $className
	 * @throws Exception\ValidationTypeException
	 */
	public static function overrideType($name, $className)
	{
		if ( ! isset(self::$typesMap[$name])) {
			throw new Exception\ValidationTypeException("Cannto override unknown validation type $name");
		}

		self::$typesMap[$name] = $className;
	}

	/**
	 * Get all validation type map
	 * @return array
	 */
	public static function getTypesMap()
	{
		return self::$typesMap;
	}

	/**
	 * Check if the received value is valid (currently by checking error exception)
	 * @param string $value
	 */
	public function isValid($value)
	{
		try {
			$this->validate($value);
		} catch (Exception\ValidationFailure $exception) {
			return false;
		}
		
		return true;
	}
}
