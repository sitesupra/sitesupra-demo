<?php

namespace Supra\Database\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types;

class UtcDateTimeType extends Types\DateTimeType
{

	static private $utc = null;

	public function convertToDatabaseValue($value, AbstractPlatform $platform)
	{
		if ($value === null) {
			return null;
		}

		$value->setTimezone((self::$utc) ? self::$utc : (self::$utc = new \DateTimeZone('UTC')));

		return $value->format($platform->getDateTimeFormatString());
	}

	public function convertToPHPValue($value, AbstractPlatform $platform)
	{
		if ($value === null) {
			return null;
		}

		$format = $platform->getDateTimeFormatString();
		$timezone = (self::$utc) ? self::$utc : (self::$utc = new \DateTimeZone('UTC'));

		$val = \DateTime::createFromFormat($format, $value, $timezone);

		if ( ! $val instanceof \DateTime) {
			throw ConversionException::conversionFailed($value, $this->getName());
		}
		
		$timezone = new \DateTimeZone(date_default_timezone_get());
		$val->setTimeZone($timezone);
		
		return $val;
	}

}