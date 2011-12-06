<?php

namespace Supra\Payment\Entity\Transaction;

use \DateTime;

/**
 * @Entity
 * @Table(indexes={
 * 		@index(name="transactionIdIdx", columns={"transactionId"})
 * })
 */
class TransactionLogEntry extends Entity
{
	
	/**
	 * @param Transaction $transaction 
	 */
	function __construct(Transaction $transaction)
	{
		$this->copy($transaction);

		parent::__construct();

		$this->transactionId = $transaction->getId();

		$this->logTime = new DateTime('now');

	}

	/**
	 * @Column(type="datetime")
	 * @var DateTime
	 */
	protected $logTime;

	/**
	 * @Column(type="string", nullable=false)
	 * @var string
	 */
	protected $transactionId;

}
