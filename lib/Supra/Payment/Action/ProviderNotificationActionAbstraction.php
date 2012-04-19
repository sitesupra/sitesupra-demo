<?php

namespace Supra\Payment\Action;

use Supra\ObjectRepository\ObjectRepository;
use Supra\Request\HttpRequest;
use Supra\Request\RequestData;
use Supra\Payment\Entity\Order\Order;
use Supra\Payment\Order\RecurringOrderStatus;
use Supra\Payment\Entity\Transaction\Transaction;
use Supra\Payment\Order\OrderStatus;
use Supra\Payment\Order\OrderProvider;
use Supra\Payment\Entity\Order\ShopOrder;
use Supra\Payment\Entity\Order\RecurringOrder;
use Supra\Payment\Provider\PaymentProviderAbstraction;
use Supra\Payment\Provider\Event\ProviderNotificationEventArgs;
use Supra\Payment\Transaction\TransactionProvider;
use Supra\Payment\Transaction\TransactionStatus;
use Supra\Payment\RecurringPayment\RecurringPaymentStatus;

abstract class ProviderNotificationActionAbstraction extends ActionAbstraction
{

	abstract public function getNotificationData();

	abstract public function setNotificationData($notificationData);

	/**
	 * @param ShopOrder $order 
	 */
	protected function handleShopOrder(ShopOrder $order)
	{
		$transaction = null;

		try {

			$transaction = $order->getTransaction();

			$statusBefore = $transaction->getStatus();

			$this->processShopOrder($order);

			$statusNow = $transaction->getStatus();

			$this->fireProviderNotificationEvent();

			\Log::debug('Provider Notification Abstraction Transaction Status ', $statusBefore, ' ---> ', $statusNow);
		} catch (Exception\RuntimeException $e) {

			if ( ! empty($transaction)) {
				$transaction->setStatus(TransactionStatus::SYSTEM_ERROR);
			}
		}
	}

	/**
	 * @param ShopOrder $order
	 */
	abstract protected function processShopOrder(ShopOrder $order);

	/**
	 * @param RecurringOrder $order 
	 */
	protected function handleRecurringOrder(RecurringOrder $order)
	{
		$recurringPayment = null;

		try {
			
			$recurringPayment = $order->getRecurringPayment();

			$statusBefore = $recurringPayment->getStatus();

			$this->processRecurringOrder($order);

			$statusNow = $recurringPayment->getStatus();

			$this->fireProviderNotificationEvent();

			\Log::debug('Provider Notification Abstraction RecurringPayment Status ', $statusBefore, ' ---> ', $statusNow);
		} catch (Exception\RuntimeException $e) {

			if(!empty($recurringPayment)) {
				$recurringPayment->setStatus(RecurringPaymentStatus::SYSTEM_ERROR);
			}
		}
	}

	/**
	 * @param $order RecurringOrder
	 */
	abstract protected function processRecurringOrder(RecurringOrder $order);

	abstract protected function getProviderNotificationEventArgs();

	protected function fireProviderNotificationEvent()
	{
		$eventArgs = $this->getProviderNotificationEventArgs();

		$eventManager = ObjectRepository::getEventManager($this);
		$eventManager->fire(PaymentProviderAbstraction::EVENT_PROVIDER_NOTIFICATION, $eventArgs);
	}

}
