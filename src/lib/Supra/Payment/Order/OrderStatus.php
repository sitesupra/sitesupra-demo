<?php

namespace Supra\Payment\Order;

class OrderStatus
{
	const OPEN = 100; // Order is "open" and items can be added / removed.

	const FINALIZED = 200; // Order is "closed" and now must be either paid or deleted.
	
	const PAYMENT_STARTED = 300; // Order has been dispatched to payment provider. A payment entity has been created for this order.
	
	const PAYMENT_START_ERROR = 400;

	const SYSTEM_ERROR = 1000;

	static function getKnownStatuses()
	{
		return array(
			self::OPEN,
			self::FINALIZED,
			self::PAYMENT_START_ERROR,
			self::PAYMENT_STARTED,
			self::SYSTEM_ERROR,
		);
	}

	/**
	 * Validates value of $status to be one of known statuses. Throws exception.
	 * @param integer $status 
	 */
	static function validate($status)
	{
		if ( ! in_array($status, static::getKnownStatuses())) {
			throw new Exception\RuntimeException('Status value "' . $status . '" not recognized. Use constants from ' . get_called_class() . ' class.');
		}
	}

}

