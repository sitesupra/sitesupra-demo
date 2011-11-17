<?php

namespace Supra\Payment\Transaction;

class TransactionType
{
	const PURCHASE = 1100;
	const RECURRING_PURCHASE = 1200;

	const PURCHASE_REFUND = 2100;
	const RECURRING_PURCHASE_REFUND = 2200;
	
	const TRANSFER = 3100;
	
	static $knonwnTypes = array(
		self::PURCHASE,
		self::RECURRING_PURCHASE,
		self::PURCHASE_REFUND,
		self::RECURRING_PURCHASE_REFUND,
		self::TRANSFER
	);
	
}
