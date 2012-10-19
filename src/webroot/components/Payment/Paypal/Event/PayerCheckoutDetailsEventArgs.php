<?php

namespace Project\Payment\Paypal\Event;

use Supra\Payment\Provider\Event\PaymentEventArgsAbstraction;

class PayerCheckoutDetailsEventArgs extends PaymentEventArgsAbstraction
{

	/**
	 * @var array
	 */
	protected $payerCheckoutDetails;

	/**
	 * @return array
	 */
	public function getPayerCheckoutDetails()
	{
		return $this->payerCheckoutDetails;
	}

	/**
	 * @param array $payerCheckoutDetails 
	 */
	public function setPayerCheckoutDetails($payerCheckoutDetails)
	{
		$this->payerCheckoutDetails = $payerCheckoutDetails;
	}

}
