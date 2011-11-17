<?php

namespace Supra\Payment\Provider;

use Supra\Payment\Provider\PaymentProviderAbstraction;

class PaymentProviderCollection
{
	protected $paymentProviders;

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
		if (empty($this->paymentProvider[$paymentProviderId])) {
			throw new Exception\RuntimeException('Payment provider "' . $paymentProviderId . '" is not found in collection.');
		}

		return $this->paymentProviders[$paymentProviderId];
	}

}

