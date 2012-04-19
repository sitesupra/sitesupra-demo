<?php

namespace Supra\Payment\Entity\Transaction;

use Supra\Database;
use Supra\Payment\Entity\Abstraction\PaymentEntityParameter;

/**
 * @Entity
 * @Table(indexes={
 * 		@index(name="transactionIdIdx", columns={"transactionId"}),
 * 		@index(name="phaseNameIdx", columns={"phaseName"})
 * })
 */
class TransactionParameter extends PaymentEntityParameter
{
	/**
	 * @return Transaction
	 */
	public function getTransaction()
	{
		return $this->paymentEntity;
	}

		/**
	 * @param Transaction $transaction 
	 */
	public function setTransaction(Transaction $transaction)
	{
		$this->paymentEntity = $transaction;
	}

}

