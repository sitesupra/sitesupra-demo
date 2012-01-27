<?php

namespace Supra\Payment\RecurringPayment;

use Supra\Payment\Abstraction\PaymentEntityProviderAbstraction;
use Supra\Payment\Entity\RecurringPayment\RecurringPayment;
use Supra\Payment\Entity\RecurringPayment\RecurringPaymentParameter;
use Supra\Payment\Entity\RecurringPayment\RecurringPaymentTransaction;

class RecurringPaymentProvider extends PaymentEntityProviderAbstraction
{

	/**
	 * @return string
	 */
	protected function getEntityClassName()
	{
		return RecurringPayment::CN();
	}

	/**
	 * @return string
	 */
	protected function getEntityParameterClassName()
	{
		return RecurringPaymentParameter::CN();
	}

	public function store(RecurringPayment $entity)
	{
		$lastTransaction = $entity->getLastTransaction();

		if ( ! empty($lastTransaction)) {
			$this->store($lastTransaction, true);
		}

		$transactions = $entity->getTransactions();

		if ( ! empty($transactions)) {
			foreach ($transactions as $transaction) {
				$this->store($transaction, true);
			}
		}

		parent::store($entity);
	}

}
