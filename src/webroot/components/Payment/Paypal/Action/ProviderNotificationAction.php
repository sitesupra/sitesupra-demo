<?php

namespace Project\Payment\Paypal\Action;

use Project\Payment\Paypal;
use Supra\Request\HttpRequest;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Payment\Transaction\TransactionStatus;
use Supra\Payment\RecurringPayment\RecurringPaymentStatus;
use Supra\Payment\Provider\Event\ProviderNotificationEventArgs;
use Supra\Payment\Provider\PaymentProviderAbstraction;
use Supra\Payment\Order\OrderStatus;
use Supra\Payment\Order\RecurringOrderStatus;
use Supra\Payment\Entity\TransactionParameter;
use Supra\Payment\Entity\Order\Order;
use Supra\Payment\Entity\Order\ShopOrder;
use Supra\Payment\Entity\Order\RecurringOrder;
use Supra\Payment\Entity\RecurringPayment\RecurringPaymentTransaction;
use Supra\Payment\Action\ProviderNotificationActionAbstraction;
use Supra\Payment\Entity\Transaction\Transaction;

class ProviderNotificationAction extends ProviderNotificationActionAbstraction
{
	const NOTIFICATION_KEY_TXN_ID = 'txn_id';
	const NOTIFICATION_KEY_IPN_ID = 'ipn_track_id';
	const NOTIFICATION_KEY_TXN_TYPE = 'txn_type';
	const NOTIFICATION_KEY_PAYMENT_STATUS = 'payment_status';
	const NOTIFICATION_KEY_PARENT_TXN_ID = 'parent_txn_id';
	const NOTIFICATION_KEY_RECURRING_PAYMENT_ID = 'recurring_payment_id';
	const NOTIFICATION_KEY_AMOUNT = 'amount';

	/**
	 * @var Order
	 */
	protected $order;

	/**
	 * @var array
	 */
	protected $notificationData;

	/**
	 * @return Order
	 */
	public function getOrder()
	{
		if (empty($this->order)) {
			throw new Paypal\Exception\RuntimeException('Order is not set.');
		}

		return $this->order;
	}

	/**
	 * @param Order $order 
	 */
	public function setOrder($order)
	{
		$this->order = $order;
	}

	public function getNotificationData()
	{
		return $this->notificationData;
	}

	public function setNotificationData($notificationData)
	{
		$this->notificationData = $notificationData;
	}

	public function execute()
	{
		$paymentProvider = $this->getPaymentProvider();
		$orderProvider = $this->getOrderProvider();

		$request = $this->getRequest();

		/* @var $request HttpRequest */
		$notificationData = $request->getPost()->getArrayCopy();

		\Log::debug('AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA:', $notificationData);

		if ($paymentProvider->validateIpn($notificationData) == false) {
			throw new Exception\RuntimeException('Paypal IPN verification failed.');
		}

		$this->setNotificationData($notificationData);

		if ( ! empty($notificationData[self::NOTIFICATION_KEY_TXN_TYPE])) {

			$txnType = $notificationData[self::NOTIFICATION_KEY_TXN_TYPE];

			switch ($txnType) {

				case 'express_checkout': {

						$paypalTransactionId = $notificationData[self::NOTIFICATION_KEY_TXN_ID];

						$paymentEntity = $this->getPaymentEntityByPaypalTransactionId($paypalTransactionId);

						$order = $orderProvider->getOrderByPaymentEntity($paymentEntity);

						if (empty($order)) {
							throw new Paypal\Exception\RuntimeException('Could not get shop order from payment entity with id "' . $paymentEntity->getId() . '"');
						}

						$this->setOrder($order);

						$this->handleShopOrder($order);
					} break;

				case 'recurring_payment':
				case 'recurring_payment_profile_created': {

						$paypalRecurringPaymentId = $notificationData[self::NOTIFICATION_KEY_RECURRING_PAYMENT_ID];

						$paymentEntity = $this->getPaymentEntityByPaypalRecurringPaymentId($paypalRecurringPaymentId);

						$order = $orderProvider->getOrderByPaymentEntity($paymentEntity);

						if (empty($order)) {
							throw new Paypal\Exception\RuntimeException('Could not get recurring order from payment entity with id "' . $paymentEntity->getId() . '"');
						}

						$this->setOrder($order);

						$this->handleRecurringOrder($order);
					} break;

				default: {
						throw new Paypal\Exception\RuntimeException('Do not know what to do with "' . $txnType . '" notification.');
					}
			}
		} else {

			$paymentStatus = $notificationData[self::NOTIFICATION_KEY_PAYMENT_STATUS];

			if ($paymentStatus == Paypal\PaypalPaymentStatus::REVERSED) {

				$parentTransactionId = $notificationData[self::NOTIFICATION_KEY_PARENT_TXN_ID];

				$paymentEntity = $this->getPaymentEntityByPaypalTransactionId($parentTransactionId);

				$orderProvider = $this->getOrderProvider();

				$order = $orderProvider->getOrderByPaymentEntity($paymentEntity);

				if ($paymentEntity instanceof Transaction) {

					$paymentEntity->setStatus(TransactionStatus::PAYER_CANCELED);

					\Log::debug('Provider Notification Paypal Transaction Status: ', $paymentEntity->getStatus());
				} else if ($paymentEntity instanceof RecurringPaymentTransaction) {

					$paymentEntity->setStatus(TransactionStatus::PAYER_CANCELED);

					$paymentEntity->getRecurringPayment()
							->setStatus(RecurringPaymentStatus::PAYER_CANCELED);
				}

				$this->setOrder($order);

				$this->fireProviderNotificationEvent();
				
				$orderProvider->store($order);
			}
		}
	}

	/**
	 * @return string
	 */
	protected function getNotificationPhaseName()
	{
		$notificationData = $this->getNotificationData();

		$parts = array();

		if ( ! empty($notificationData[self::NOTIFICATION_KEY_TXN_TYPE])) {
			$parts[] = $notificationData[self::NOTIFICATION_KEY_TXN_TYPE];
		}
		if ( ! empty($notificationData[self::NOTIFICATION_KEY_PAYMENT_STATUS])) {
			$parts[] = $notificationData[self::NOTIFICATION_KEY_PAYMENT_STATUS];
		}

		$ipnId = join('-', $parts);

		return Paypal\PaymentProvider::PHASE_NAME_IPN . $ipnId;
	}

	protected function processShopOrder(ShopOrder $order)
	{
		$orderProvider = $this->getOrderProvider();

		$notificationData = $this->getNotificationData();

		$ipnPhaseName = $this->getNotificationPhaseName();

		$order->addToPaymentEntityParameters($ipnPhaseName, $notificationData);

		$transaction = $order->getTransaction();

		$paymentStatus = $notificationData[self::NOTIFICATION_KEY_PAYMENT_STATUS];

		switch ($paymentStatus) {

			case Paypal\PaypalPaymentStatus::COMPLETED: {

					$transaction->setStatus(TransactionStatus::SUCCESS);
				} break;

			case Paypal\PaypalPaymentStatus::PENDING: {

					$transaction->setStatus(TransactionStatus::PENDING);
				} break;

			case Paypal\PaypalPaymentStatus::REVERSED: {

					$transaction->setStatus(TransactionStatus::PAYER_CANCELED);
				} break;

			default: {

					$transaction->setStatus(TransactionStatus::SYSTEM_ERROR);
				}
		}

		$orderProvider->store($order);
	}

	protected function processRecurringOrder(RecurringOrder $order)
	{
		$notificationData = $this->getNotificationData();

		$txnType = $notificationData[self::NOTIFICATION_KEY_TXN_TYPE];

		switch ($txnType) {

			case 'recurring_payment': {

					$this->paypalRecurringPaymentReceived();
				} break;

			case 'recurring_payment_profile_created': {

					$this->paypalRecurringPaymentProfileCreated();
				} break;

			default: {
					throw new Paypal\Exception\RuntimeException('Do not know what to do with Paypal IPN txnType "' . $txnType . '".');
				}
		}
	}

	protected function paypalRecurringPaymentReceived()
	{
		$orderProvider = $this->getOrderProvider();

		$order = $this->getOrder();

		if ($order instanceof RecurringOrder) {

			$notificationData = $this->getNotificationData();

			$txnType = $notificationData[self::NOTIFICATION_KEY_TXN_TYPE];

			$paymentStatus = $notificationData[self::NOTIFICATION_KEY_PAYMENT_STATUS];

			$recurringPayment = $order->getRecurringPayment();

			// Got money!
			if ($paymentStatus == Paypal\PaypalPaymentStatus::COMPLETED) {

				$amount = $notificationData[self::NOTIFICATION_KEY_AMOUNT];

				$transaction = new RecurringPaymentTransaction();
				$transaction->addToParameters($txnType, $notificationData);
				$transaction->setAmount($amount);

				$recurringPayment->setStatus(RecurringPaymentStatus::PAID);
				$recurringPayment->addTransaction($transaction);

				$this->fireProviderNotificationEvent();
			}

			$orderProvider->store($order);
		} else {
			throw new Paypal\Exception\RuntimeException('Do not know how to handle recurring payments in context of non-RecurringOrder.');
		}
	}

	/**
	 * @param string $paypalTransactionId 
	 */
	private function getPaymentEntityByPaypalRecurringPaymentId($paypalRecurringPaymentId)
	{
		$paymentEntityProvider = $this->getPaymentEntityProvider();

		$phaseName = Paypal\PaymentProvider::PHASE_NAME_CREATE_RECURRING_PAYMENT;
		$name = Paypal\PaymentProvider::TRANSACTION_PARAMETER_PROFILEID;
		$foundPaymentEntities = $paymentEntityProvider->findByParameterPhaseAndNameAndValue($phaseName, $name, $paypalRecurringPaymentId);

		if (empty($foundPaymentEntities)) {
			throw new Paypal\Exception\RuntimeException('Paypal payment entities not found for Paypal recurring payment  id "' . $paypalRecurringPaymentId . '"');
		}

		if (count($foundPaymentEntities) > 1) {
			throw new Paypal\Exception\RuntimeException('Found more than one payment entity for Paypal recurring payment id"' . $paypalRecurringPaymentId . '"');
		}

		/* @var $paymentEntity PaymentEntity */
		$paymentEntity = array_pop($foundPaymentEntities);

		return $paymentEntity;
	}

	/**
	 * @param string $paypalTransactionId 
	 */
	private function getPaymentEntityByPaypalTransactionId($paypalTransactionId)
	{
		$paymentEntityProvider = $this->getPaymentEntityProvider();

		$phaseName = Paypal\PaymentProvider::PHASE_NAME_DO_PAYMENT;
		$name = Paypal\PaymentProvider::TRANSACTION_PARAMETER_TRANSACTIONID;
		$foundPaymentEntities = $paymentEntityProvider->findByParameterPhaseAndNameAndValue($phaseName, $name, $paypalTransactionId);

		if (empty($foundPaymentEntities)) {
			throw new Paypal\Exception\RuntimeException('Paypal payment entities not found for Paypal transaction id "' . $paypalTransactionId . '"');
		}

		if (count($foundPaymentEntities) > 1) {
			throw new Paypal\Exception\RuntimeException('Found more than one payment entity for Paypal transaction id"' . $paypalTransactionId . '"');
		}

		/* @var $paymentEntity PaymentEntity */
		$paymentEntity = array_pop($foundPaymentEntities);

		return $paymentEntity;
	}

	/**
	 * @return ProviderNotificationEventArgs 
	 */
	protected function getProviderNotificationEventArgs()
	{
		$order = $this->getOrder();
		$notificationData = $this->getNotificationData();

		$eventArgs = new ProviderNotificationEventArgs();
		$eventArgs->setOrder($order);
		$eventArgs->setNotificationData($notificationData);

		return $eventArgs;
	}

}

