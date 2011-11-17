<?php

namespace Supra\Payment\Entity\Transaction;

/**
 * @Entity
 * @Table(indexes={
 * 		@index(name="transactionIdIdx", columns={"transactionId"})
 * })
 */
class TransactionLogEntry extends Entity
{
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
