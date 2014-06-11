<?php

namespace Supra\Payment\Entity\Order;

use Supra\Payment\Order\OrderStatus;
use Supra\Payment\Entity\Transaction\Transaction;
use Supra\Payment\Transaction\TransactionStatus;

/**
 * @Entity 
 */
class ShopOrder extends Order
{

	/**
	 * @OneToOne(targetEntity="Supra\Payment\Entity\Transaction\Transaction")
	 * @JoinColumn(name="transactionId", referencedColumnName="id")
	 * @var Transaction
	 */
	protected $transaction;

	/**
	 * @return Transaction
	 */
	public function getTransaction()
	{
		return $this->transaction;
	}

	/**
	 * @param Transaction $transaction 
	 */
	public function setTransaction(Transaction $transaction)
	{
		$this->transaction = $transaction;
	}

	/**
	 * @return string 
	 */
	public function getPaymentProviderId()
	{
		$paymentProviderId = null;

		$transaction = $this->getTransaction();

		if ( ! empty($transaction)) {
			return $transaction->getPaymentProviderId();
		}

		return $paymentProviderId;
	}

	/**
	 * @param string $phaseName
	 * @param mixed $data
	 */
	public function addToPaymentEntityParameters($phaseName, $data)
	{
		$transaction = $this->getTransaction();
		$transaction->addToParameters($phaseName, $data);
	}

	/**
	 * @param integer $status
	 */
	public function setStatus($status)
	{
		OrderStatus::validate($status);
		$this->status = $status;
	}

	/**
	 * @param string $phaseName
	 * @param string  $name
	 * @return mixed
	 */
	public function getPaymentEntityParameterValue($phaseName, $name)
	{
		$transaction = $this->getTransaction();

		if (empty($transaction)) {
			throw new Exception\RuntimeException('Transaction entity not set.');
		}

		$value = $transaction->getParameterValue($phaseName, $name);

		return $value;
	}

	/**
	 * @return string
	 */
	public function getPaymentEntityId()
	{
		$paymentEntityId = $this->getTransaction()
				->getId();

		return $paymentEntityId;
	}

	/**
	 * @return boolean
	 */
	public function isPaid()
	{
		return $this->getTransaction()->getStatus() == TransactionStatus::SUCCESS;
	}

}
