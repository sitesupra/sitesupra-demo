<?php

namespace Supra\Database\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\DateTimeType;
use DateTimeZone;
use DateTime;

class UtcDateTimeType extends DateTimeType
{
	const CN = __CLASS__;

	/**
	 * UTC timezone object
	 * @var DateTimeZone
	 */
	static private $utc = null;

	/**
	 * @return DateTimeZone
	 */
	private function getUtcDateTimeZone()
	{
		return self::$utc ?: (self::$utc = new DateTimeZone('UTC'));
	}
	
	/**
	 * Converts datetime to datetime string in UTC
	 * @param DateTime $value
	 * @param AbstractPlatform $platform
	 * @return string
	 */
	public function convertToDatabaseValue($value, AbstractPlatform $platform)
	{
		if ($value === null) {
			return null;
		}

		$timezone = $this->getUtcDateTimeZone();
		$value->setTimezone($timezone);

		return $value->format($platform->getDateTimeFormatString());
	}

	/**
	 * Converts UTC string into datetime object
	 * @param string $value
	 * @param AbstractPlatform $platform
	 * @return DateTime
	 */
	public function convertToPHPValue($value, AbstractPlatform $platform)
	{
		if ($value === null) {
			return null;
		}

		$format = $platform->getDateTimeFormatString();
		$timezone = $this->getUtcDateTimeZone();

		$val = DateTime::createFromFormat($format, $value, $timezone);

		if ( ! $val instanceof DateTime) {
			throw ConversionException::conversionFailed($value, $this->getName());
		}
		
		$timezone = new DateTimeZone(date_default_timezone_get());
		$val->setTimeZone($timezone);
		
		return $val;
	}

}
