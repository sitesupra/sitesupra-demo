<?php

namespace Project\Payment\Paypal\Event;

use Supra\Payment\Provider\Event\EventArgsAbstraction;

class PayerCheckoutDetailsEventArgs extends EventArgsAbstraction
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
