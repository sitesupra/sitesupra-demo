<?php

namespace Supra\Payment\Provider;

use Supra\Payment\Provider\PaymentProviderAbstraction;

class PaymentProviderCollection
{

	/**
	 * @var array
	 */
	protected $paymentProviders;

	/**
	 * @param PaymentProviderAbstraction $paymentProvider 
	 */
	public function add(PaymentProviderAbstraction $paymentProvider)
	{
		$this->paymentProviders[$paymentProvider->getId()] = $paymentProvider;
	}

	/**
	 * Returns payment provider by its Id.
	 * @param string $paymentProviderId
	 * @return PaymentProviderAbstraction 
	 */
	public function get($paymentProviderId)
	{
		if (empty($this->paymentProviders[$paymentProviderId])) {
			throw new Exception\RuntimeException('Payment provider "' . $paymentProviderId . '" is not found in collection. Have ' . join(', ', array_keys($this->paymentProviders)) . '.');
		}

		return $this->paymentProviders[$paymentProviderId];
	}

	/**
	 * @return array
	 */
	public function getIds()
	{
		return array_keys($this->paymentProviders);
	}

}

