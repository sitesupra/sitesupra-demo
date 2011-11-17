<?php

namespace Supra\Payment\Transaction;

class TransactionStatus
{
	const INITIALIZED = 0;
	const FAILED = 100;
	const SUCCESS = 200;
	const IN_PROGRESS = 400;
	
	const PROVIDER_ERROR = 900;
	const SYSTEM_ERROR = 910;
	
	static $knonwnStatuses = array(
			self::INITIALIZED,
			self::FAILED,
			self::SUCCESS,
			self::IN_PROGRESS,
			self::PROVIDER_ERROR,
			self::SYSTEM_ERROR
		);
}
