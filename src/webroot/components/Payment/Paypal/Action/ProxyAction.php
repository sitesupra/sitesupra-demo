<?php

namespace Project\Payment\Paypal\Action;

use Supra\Payment\Action\ProxyActionAbstraction;
use Project\Payment\Paypal;
use Supra\Payment\Order\OrderProvider;
use Supra\Payment\Order\OrderStatus;
use Supra\Payment\Transaction\TransactionProvider;
use Supra\Payment\Transaction\TransactionStatus;

class ProxyAction extends ProxyActionAbstraction
{

	/**
	 * @var Paypal\PaymentProvider
	 */
	protected $paymentProvider;

	/**
	 * @return array
	 */
	protected function preparePayment()
	{
		/** @var $paymentProvider Paypal\PaymentProvider */
		$paymentProvider = $this->paymentProvider;

		$orderProvider = new OrderProvider();
		$transactionProvider = new TransactionProvider();

		$transaction = $this->order->getTransaction();

		$this->proxyData = $paymentProvider->makeSetExpressCheckoutCall($this->order);

		\Log::debug('SET EXPRESS CHECKOUT RESULT: ', $this->proxyData);

		if (empty($this->proxyData[Paypal\PaymentProvider::REQUEST_KEY_TOKEN])) {

			$this->order->setStatus(OrderStatus::PAYMENT_FAILED);
			$orderProvider->store($this->order);

			$transaction->setStatus(TransactionStatus::PROVIDER_ERROR);
			$transactionProvider->store($transaction);

			throw new Paypal\Exception\RuntimeException('Did not get TOKEN from Paypal\'s SetExpressCheckout.');
		}
	}

	public function getRedirectQueryData()
	{
		return array(
				'cmd' => '_express-checkout',
				'token' => $this->proxyData[Paypal\PaymentProvider::REQUEST_KEY_TOKEN]
		);
	}

	protected function beginPaymentProcedure()
	{
		$this->redirectUrl = $this->paymentProvider->getPaypalRedirectUrl();

		$this->redirectToPaymentProvider();
	}

}
