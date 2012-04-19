<?php

namespace Supra\Payment\Action;

use Supra\Payment\Provider\Event\CustomerReturnEventArgs;
use Supra\Payment\Provider\PaymentProviderAbstraction;
use Supra\Payment\Entity\Order\Order;
use Supra\Payment\Entity\Order\ShopOrder;
use Supra\Payment\Entity\Order\RecurringOrder;
use Supra\Payment\Entity\Transaction\Transaction;
use Supra\Payment\Order\OrderProvider;
use Supra\Payment\Order\OrderStatus;
use Supra\Payment\Transaction\TransactionStatus;
use Supra\Payment\Transaction\TransactionProvider;
use Supra\Response\HttpResponse;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Payment\RecurringPayment\RecurringPaymentStatus;

abstract class CustomerReturnActionAbstraction extends ActionAbstraction
{
	const QUERY_KEY_SHOP_ORDER_ID = 'shopOrderId';
	const QUERY_KEY_RECURRING_ORDER_ID = 'recurringOrderId';

	protected function handleShopOrder(ShopOrder $order)
	{
		$transaction = null;

		try {
			$transaction = $order->getTransaction();

			$statusBefore = $transaction->getStatus();

			$this->processShopOrder($order);

			$statusNow = $transaction->getStatus();

			\Log::debug('Customer Return Abstraction Transaction Status ', $statusBefore, ' ---> ', $statusNow);

			$this->fireCustomerReturnEvent();
		} catch (Exception\RuntimeException $e) {

			if ( ! empty($transaction)) {
				$transaction->setStatus(TransactionStatus::SYSTEM_ERROR);
			}
		}
	}

	/**
	 * @param $order ShopOrder
	 */
	protected abstract function processShopOrder(ShopOrder $order);

	protected function handleRecurringOrder(RecurringOrder $order)
	{
		$recurringPayment = null;

		try {
			$recurringPayment = $order->getRecurringPayment();

			$statusBefore = $recurringPayment->getStatus();

			$this->processRecurringOrder($order);

			$statusNow = $recurringPayment->getStatus();

			\Log::debug('Customer Return Abstraction RecurringPayment Status ', $statusBefore, ' ---> ', $statusNow);

			$this->fireCustomerReturnEvent();
		} catch (Exception\RuntimeException $e) {

			if ( ! empty($recurringPayment)) {
				$recurringPayment->setStatus(RecurringPaymentStatus::SYSTEM_ERROR);
			}
		}
	}

	/**
	 * @param $order RecurringOrder
	 */
	protected abstract function processRecurringOrder(RecurringOrder $order);
		
	abstract protected function getCustomerReturnEventArgs();

	protected function fireCustomerReturnEvent()
	{
		$eventManager = ObjectRepository::getEventManager($this);

		$eventArgs = $this->getCustomerReturnEventArgs();

		$eventManager->fire(PaymentProviderAbstraction::EVENT_CUSTOMER_RETURN, $eventArgs);
	}

}
