<?php

namespace Supra\Payment\Action;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Html\HtmlTag;
use Supra\Response\TwigResponse;
use Supra\Payment\Order\OrderProvider;
use Supra\Payment\Entity\Order\Order;
use Supra\Payment\Entity\Order\ShopOrder;
use Supra\Payment\Entity\Order\RecurringOrder;
use Supra\Payment\Entity\TransactionParameter;
use Supra\Payment\Provider\PaymentProviderAbstraction;
use Supra\Payment\Provider\Event\ProxyEventArgs;
use Supra\Payment\Order\OrderStatus;
use Supra\Payment\Transaction\TransactionStatus;
use Supra\Payment\Transaction\TransactionProvider;

abstract class ProxyActionAbstraction extends ActionAbstraction
{

	protected function getProxyEventArgs()
	{
		throw new Exception\RuntimeException('Not implemented yet.');
	}

	private function fireProxyEvent()
	{
		$eventManager = ObjectRepository::getEventManager($this);

		$eventArgs = $this->getProxyEventArgs();

		$eventManager->fire(PaymentProviderAbstraction::EVENT_PROXY, $eventArgs);
	}

	/**
	 * @param string $parameterKeyName
	 * @return Order
	 */
	protected function fetchOrderFromRequest($parameterKeyName)
	{
		$request = $this->getRequest();

		$orderId = $request->getParameter($parameterKeyName);

		$order = $this->fetchOrder($orderId);

		return $order;
	}

	/**
	 * @param string $orderId
	 * @return Order
	 */
	protected function fetchOrder($orderId)
	{
		$order = $this->getOrderProvider()
				->getOrder($orderId);

		$validStatuses = array(
			OrderStatus::FINALIZED,
			//OrderStatus::PAYMENT_STARTED,
		);

		if ( ! in_array($order->getStatus(), $validStatuses)) {
			throw new Exception\RuntimeException('Order "' . $orderId . '" is not FINALIZED!');
		}

		return $order;
	}

}
