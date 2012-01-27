<?php

namespace Supra\Payment\RecurringPayment;

class RecurringPaymentStatus
{
	const REQUESTED = 100;
	const CONFIRMED = 150;
	const TRIAL = 800;
	const PENDING = 200;
	const PAID = 300;
	const LATE = 400;
	const SUSPENDED = 500;
	const PAYER_CANCELED = 600;
	const SELLER_CANCELED = 700;
	const SYSTEM_ERROR = 1000;
	const PROVIDER_ERROR = 1100;

	/**
	 * @return array
	 */
	static function getKnownStatuses()
	{
		return array(
			self::REQUESTED,
			self::CONFIRMED,
			self::TRIAL,
			self::PENDING,
			self::PAID,
			self::LATE,
			self::SUSPENDED,
			self::PAYER_CANCELED,
			self::SELLER_CANCELED,
			self::SYSTEM_ERROR,
			self::PROVIDER_ERROR,
		);
	}

	/**
	 * Validates value of $status to be one of known statuses. Throws exception.
	 * @param integer $status 
	 */
	static

	function validate($status)
	{
		if ( ! in_array($status, static::getKnownStatuses())) {
			throw new Exception\RuntimeException('Status value "' . $status . '" not recognized. Use constants from ' . get_called_class() . ' class.');
		}
	}

}

