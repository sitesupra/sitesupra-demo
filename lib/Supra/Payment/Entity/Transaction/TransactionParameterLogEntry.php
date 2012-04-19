<?php

namespace Supra\Payment\Entity\Transaction;

use Supra\Payment\Entity\Abstraction\PaymentEntityParameter;
use \DateTime;
use Supra\Payment\Entity\Abstraction\LogEntry;

/**
 * @Entity
 * @Table(indexes={
 * 		@index(name="transactionParameterIdIdx", columns={"transactionParameterId"})
 * })
 */
class TransactionParameterLogEntry extends TransactionParameter implements LogEntry
{
	/**
	 * @Column(type="datetime", nullable=false)
	 * @var DateTime;
	 */
	protected $logTime;

	/**
	 * @Column(type="string", nullable=false)
	 * @var string
	 */
	protected $transactionParameterId;

	/**
	 * @param TransactionParameter $transactionParameter 
	 */
	function __construct(TransactionParameter $transactionParameter)
	{
		$this->copy($transactionParameter);
		parent::__construct();

		$this->logTime = new DateTime('now');
		$this->transactionParameterId = $transactionParameter->getId();
	}

	/**
	 * @return string
	 */
	public function getTransactionParameterId()
	{
		return $this->transactionParameterId;
	}

}
