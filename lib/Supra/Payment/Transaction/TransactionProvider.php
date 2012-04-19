<?php

namespace Supra\Payment\Transaction;

use Supra\Payment\Entity\Transaction\Transaction;
use Supra\Payment\Entity\Transaction\TransactionParameter;
use Supra\Payment\Abstraction\PaymentEntityProviderAbstraction;

class TransactionProvider extends PaymentEntityProviderAbstraction
{
	/**
	 * @return string
	 */
	protected function getEntityClassName()
	{
		return Transaction::CN();
	}

	/**
	 * @return string
	 */
	protected function getEntityParameterClassName()
	{
		return TransactionParameter::CN();
	}

}
