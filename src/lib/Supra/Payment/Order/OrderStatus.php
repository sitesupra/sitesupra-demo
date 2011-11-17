<?php

namespace Supra\Payment\Order\OrderStatus;

class OrderStatus
{
	const FRESH = 400;

	const FINALIZED = 500;

	const PAYMENT_STARTED = 600;
	const PAYMENT_RECEIVED = 650;
	const PAYMENT_CANCELED = 660;
	const PAYMENT_FAILED = 670;

	const SYSTEM_ERROR = 1000;

	static $knownStates = array(
			self::FRESH,
			self::FINALIZED,
			self::PAYMENT_STARTED,
			self::PAYMENT_RECEIVED,
			self::PAYMENT_CANCELED,
			self::PAYMENT_FAILED,
			self::SYSTEM_ERROR
	);

}
