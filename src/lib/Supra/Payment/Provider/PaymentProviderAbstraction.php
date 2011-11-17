<?php

namespace Supra\Payment\Provider;

use Supra\Payment\Entity\Transaction\Transaction;
use Supra\Payment\Entity\Order\Order;

abstract class PaymentProviderAbstraction
{
	const PROXY_URL_POSTFIX = 'proxy';
	const PROVIDER_NOTIFICATION_URL_POSTFIX = 'notification';
	const CUSTOMER_RETURN_URL_POSTFIX = 'return';
	
	const ORDER_ID = 'orderId';

	protected $proxyActionClass;
	protected $providerNotificationActionClass;
	protected $customerReturnActionClass;

	abstract function getId();

	abstract function validateTransaction(Transaction $transaction);

	abstract function send(User $user, $amount, $currency, $comment);

	/**
	 * @return Transaction 
	 */
	public function createTransaction()
	{
		$transaction = new Transaction();

		$transaction->setPaymentProviderId($this->getId());

		return $transaction;
	}

	/**
	 * @return string
	 */
	abstract function getBaseUrl();

	/**
	 * @return string
	 */
	public function getProxyActionUrl(Order $order)
	{
		$query = http_build_query(array(self::ORDER_ID => $order->getId()));
		
		return $this->getPaymentProviderBaseUrl() . '\\' . self::PROXY_URL_POSTFIX . '?' . $query;
	}

	/**
	 * @return string
	 */
	public function getCustomerReturnActionUrl($queryData)
	{
		$query = http_build_query($queryData);
		
		return $this->getPaymentProviderBaseUrl() . '\\' . self::CUSTOMER_RETURN_URL_POSTFIX . '?' . $query;
	}

	/**
	 * @return string
	 */
	public function getProviderNotificationActionUrl($queryArray)
	{
		$query = http_build_query($queryData);

		return $this->getPaymentProviderBaseUrl() . '\\' . self::PROVIDER_NOTIFICATION_URL_POSTFIX . '?' . $query;
	}

}

