<?php

namespace Supra\Payment;

use Supra\Router\UriRouter;
use Supra\Payment\Provider\PaymentProviderAbstraction;


class PaymentProviderUriRouter extends UriRouter
{
	/**
	 * @var PaymentProviderAbstraction
	 */
	protected $paymentProvider;
	
	/**
	 * @return PaymentProviderAbstraction
	 */
	public function getPaymentProvider()
	{
		return $this->paymentProvider;
	}
	
	/**
	 * @param PaymentProviderAbstraction $paymentProvider 
	 */
	public function setPaymentProvider(PaymentProviderAbstraction $paymentProvider)
	{
		$this->paymentProvider = $paymentProvider;
	}
	
}
