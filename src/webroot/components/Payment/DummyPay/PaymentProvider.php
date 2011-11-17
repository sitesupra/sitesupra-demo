<?php

namespace Project\Payment\DummyPay;

use Supra\Payment\Provider\PaymentProviderAbstraction;
use Supra\Payment\Entity\Transaction\Transaction;

class PaymentProvider extends PaymentProviderAbstraction
{
	const ID = 'DummyPay';

	public function getId()
	{
		return self::ID;
	}

	public function getName()
	{
		return 'DuymmyPay - for all Your dummy needs!';
	}

	public function getProxtostUrl()
	{
		return '/dummypay/pay';
	}

	/**
	 * @return Transaction 
	 */
	public function getNewTransaction()
	{
		$transaction = new Transaction();
		$transaction->setPaymentProviderId($this->getId());

		return $transaction;
	}

}
