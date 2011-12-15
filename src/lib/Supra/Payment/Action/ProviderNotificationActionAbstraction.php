<?php

namespace Supra\Payment\Action;

use Supra\ObjectRepository\ObjectRepository;
use Supra\Request\HttpRequest;
use Supra\Request\RequestData;
use Supra\Payment\Entity\Order\Order;
use Supra\Payment\Entity\Transaction\Transaction;
use Supra\Payment\Order\OrderStatus;
use Supra\Payment\Order\OrderProvider;
use Supra\Payment\Provider\PaymentProviderAbstraction;
use Supra\Payment\Provider\Event\ProviderNotificationEventArgs;
use Supra\Payment\Transaction\TransactionProvider;
use Supra\Payment\Transaction\TransactionStatus;

abstract class ProviderNotificationActionAbstraction extends ActionAbstraction
{

	/**
	 * @var Order
	 */
	protected $order;

	/**
	 * @var PaymentProviderAbstraction
	 */
	protected $paymentProvider;

	/**
	 * @var TransactionProvider
	 */
	protected $transactionProvider;

	/**
	 * @var OrderProvider
	 */
	protected $orderProvider;

	/**
	 * @var RequestData
	 */
	protected $notificationData;

	/**
	 * @return Transaction
	 */
	abstract public function getTransactionFromNotificationData();

	abstract protected function getNotificationPhaseName();

	abstract public function processProviderNotification();

	public function execute()
	{
		$this->transactionProvider = new TransactionProvider();
		$this->orderProvider = new OrderProvider();

		$request = $this->getRequest();
		$this->notificationData = $request->getPost();

		\Log::debug('Notification data: ', $this->notificationData->getArrayCopy());

		$transaction = $this->getTransactionFromNotificationData();

		$this->paymentProvider = $this->transactionProvider->getTransactionPaymentProvider($transaction);

		$this->order = $this->orderProvider->getOrderByTransaction($transaction);

		$statusBefore = $this->order->getStatus();

		try {

			$this->storeDataToTransactionParamaters($this->getNotificationPhaseName(), $this->notificationData);

			$this->processProviderNotification();

			$statusNow = $this->order->getStatus();

			\Log::debug('Provider Notification Abstraction ', $statusBefore, ' ---> ', $statusNow);

			if ($statusBefore == OrderStatus::PAYMENT_STARTED) {

				if ($statusNow == OrderStatus::PAYMENT_RECEIVED) {
					$this->paymentReceived();
				}
				else if ($statusNow == OrderStatus::PAYMENT_CANCELED) {
					$this->paymentCanceled();
				}
				else if ($statusNow == OrderStatus::PAYMENT_FAILED) {
					$this->paymentFailed();
				}
			}
		}
		catch (Exception\RuntimeException $e) {

			$this->order->setStatus(OrderStatus::SYSTEM_ERROR);

			$this->systemError();
		}
	}

	protected function fireProviderNotificationEvent()
	{
		$eventManager = ObjectRepository::getEventManager($this);

		$eventArgs = new ProviderNotificationEventArgs();
		$eventArgs->setOrder($this->order);
		$eventArgs->setNotificationData($this->notificationData);

		$eventManager->fire(PaymentProviderAbstraction::EVENT_PROVIDER_NOTIFICATION, $eventArgs);
	}

	protected function paymentReceived()
	{
		$this->fireProviderNotificationEvent();
	}

	protected function paymentCanceled()
	{
		$this->fireProviderNotificationEvent();
	}

	protected function paymentFailed()
	{
		$this->fireProviderNotificationEvent();
	}

	protected function systemError()
	{
		$this->fireProviderNotificationEvent();
	}

}

