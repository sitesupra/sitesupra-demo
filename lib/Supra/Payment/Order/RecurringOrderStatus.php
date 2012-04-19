<?php

namespace Supra\Payment\Order;

class RecurringOrderStatus extends OrderStatus
{
	const PAYMENT_STARTED = 500;
	const PAYMENT_FAILED_TO_START = 505;

	public static function getKnownStatuses()
	{
		$statuses = array(
			self::PAYMENT_STARTED,
			self::PAYMENT_FAILED_TO_START,
		);

		$parentStatuses = parent::getKnownStatuses();

		return array_merge($parentStatuses, $statuses);
	}	
}
