<?php

namespace Supra\Payment\Transaction;

class TransactionType
{
	const PURCHASE = 1100;
	const PURCHASE_REFUND = 2100;
	
	const RECURRING_INITIATE = 3200;
	const RECURRING_PURCHASE = 1200;
	const RECURRING_PURCHASE_REFUND = 2200;
	
	const TRANSFER = 3100;
	
	static $knownTypes = array(
		self::PURCHASE,
		self::RECURRING_PURCHASE,
		self::PURCHASE_REFUND,
		self::RECURRING_PURCHASE_REFUND,
		self::TRANSFER
	);
	
	/**
	 * Validates value of $type to be one of known types. Throws exception.
	 * @param integer $status 
	 */
	static function validate($type)
	{
		if ( ! in_array($type, self::$knownTypes)) {
			throw new Exception\RuntimeException('Unknown type "' . $type . '". Use constants from Transaction\TransactionType class.');
		}
	}	
	
}
