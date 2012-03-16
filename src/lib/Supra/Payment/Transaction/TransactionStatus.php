<?php

namespace Supra\Payment\Transaction;

class TransactionStatus
{
	const STARTED = 400;

	const PENDING = 300;

	const SUCCESS = 200;

	const PAYER_CANCELED = 500;
	const SELLER_CANCELED = 510;
	const PROVIDER_CANCELED = 520;

	const FAILED = 100;

	const PROVIDER_ERROR = 900;
	const SYSTEM_ERROR = 910;
        
        const REFUNDED = 600;

	static $knownStatuses = array(
		self::INITIALIZED,
		self::STARTED,
		self::PENDING,
		self::PAYER_CANCELED,
		self::SELLER_CANCELED,
		self::PROVIDER_CANCELED,
		self::SUCCESS,
		self::FAILED,
		self::PROVIDER_ERROR,
		self::SYSTEM_ERROR,
        self::REFUNDED
	);

	/**
	 * Validates value of $status to be one of known statuses. Throws exception.
	 * @param integer $status 
	 */
	static function validate($status)
	{
		if ( ! in_array($status, self::$knownStatuses)) {
			throw new Exception\RuntimeException('Unknown status "' . $status . '". Use constants from Transaction\TransactionStatus class.');
		}
	}

}
