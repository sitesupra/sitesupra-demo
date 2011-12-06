<?php

namespace Supra\Payment\Order;

class OrderStatus
{
	const OPEN = 400;

	const FINALIZED = 500;

	const PAYMENT_STARTED = 600;
	const PAYMENT_PENDING = 640;
	const PAYMENT_RECEIVED = 650;
	const PAYMENT_CANCELED = 660;
	const PAYMENT_FAILED = 670;

	const SYSTEM_ERROR = 1000;

	static $knownStatuses = array(
			self::OPEN,
			self::FINALIZED,
			self::PAYMENT_STARTED,
			self::PAYMENT_PENDING,
			self::PAYMENT_RECEIVED,
			self::PAYMENT_CANCELED,
			self::PAYMENT_FAILED,
			self::SYSTEM_ERROR,
	);

	/**
	 * Validates value of $status to be one of known statuses. Throws exception.
	 * @param integer $status 
	 */
	static function validate($status)
	{
		if ( ! in_array($status, self::$knownStatuses)) {
			throw new Exception\RuntimeException('Unknown status "' . $status . '". Use constants from Order\OrderStatus class.');
		}
	}

}
