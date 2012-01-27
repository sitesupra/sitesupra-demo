<?php

namespace Supra\Payment\Entity\Transaction;

use \DateTime;
use Supra\Payment\Entity\Abstraction\PaymentEntityLogEntry;
use Supra\Payment\Entity\Abstraction\LogEntry;

/**
 * @Entity
 * @Table(indexes={
 * 		@index(name="transactionIdIdx", columns={"transactionId"})
 * })
 */
class TransactionLogEntry extends Transaction implements LogEntry
{

	/**
	 * @Column(type="datetime", nullable=false)
	 * @var DateTime
	 */
	protected $logTime;

	/**
	 * @Column(type="string", nullable=false)
	 * @var string
	 */
	protected $transactionId;

	/**
	 * @param Transaction $transaction 
	 */
	function __construct(Transaction $transaction)
	{
		$this->copy($transaction);
		parent::__construct();

		$this->logTime = new \DateTime('now');
		$this->transactionId = $transaction->getId();
	}

	/**
	 * @return string
	 */
	public function getTransactionId()
	{
		return $this->transactionId;
	}

}
