<?php

namespace Supra\Payment\Entity\Transaction;

use Supra\User\Entity\AbstractUser;

/**
 * @Entity
 */
abstract class Transaction extends Entity
{

	/**
	 * @param string $paymentProviderId 
	 */
	public function setPaymentProviderId($paymentProviderId)
	{
		$this->paymentProviderId = $paymentProviderId;
	}

	/**
	 * @param string $paymentProviderAccount 
	 */
	public function setPaymentProviderAccount($paymentProviderAccount)
	{
		$this->paymentProviderAccount = $paymentProviderAccount;
	}

	/**
	 * @param AbstractUser $user 
	 */
	public function setUser(AbstractUser $user)
	{
		$this->user = $user;
	}

}
