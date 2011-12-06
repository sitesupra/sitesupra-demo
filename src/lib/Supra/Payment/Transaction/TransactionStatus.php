<?php

namespace Supra\Payment\Transaction;

class TransactionStatus
{
	const INITIALIZED = 0;
	const FAILED = 100;
	const SUCCESS = 200;
	const IN_PROGRESS = 400;
	const IN_PROGRESS2 = 410;

	const PROVIDER_ERROR = 900;
	const SYSTEM_ERROR = 910;

	static $knownStatuses = array(
			self::INITIALIZED,
			
			self::IN_PROGRESS,
			
			self::IN_PROGRESS2,
			
			self::SUCCESS,
			self::FAILED,
			
			self::PROVIDER_ERROR,
			self::SYSTEM_ERROR
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
