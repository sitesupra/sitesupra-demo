<?php

namespace Project\Payment\Transact\Action;

use Project\Payment\Transact;
use Project\Payment\Transact\Exception;
use Supra\Payment\Transaction\TransactionStatus;
use Supra\Payment\RecurringPayment\RecurringPaymentStatus;
use Supra\Payment\Provider\Event\ProviderNotificationEventArgs;
use Supra\Payment\Provider\PaymentProviderAbstraction;
use Supra\Payment\Order\OrderStatus;
use Supra\Payment\Order\RecurringOrderStatus;
use Supra\Payment\Entity\TransactionParameter;
use Supra\Payment\Entity\RecurringPayment\RecurringPaymentTransaction;
use Supra\Payment\Entity\Order;
use Supra\Payment\Entity\Transaction\Transaction;
use Supra\Payment\Action\ProviderNotificationActionAbstraction;
use Supra\Request\HttpRequest;
use Supra\ObjectRepository\ObjectRepository;

class ProviderNotificationAction extends ProviderNotificationActionAbstraction
{

	const REQUEST_KEY_TRANSACT_TRANSACTION_ID = 'ID';
	const REQUEST_KEY_MERCHANT_TRANSACTION_ID = 'MerchantID';

	/**
	 * @var Order\Order
	 */
	protected $order;

	/**
	 * @var array
	 */
	protected $notificationData;

	/**
	 * @return Transact\PaymentProvider
	 */
	protected function getPaymentProvider()
	{
		return parent::getPaymentProvider();
	}

	/**
	 * @return array
	 */
	public function getNotificationData()
	{
		if (empty($this->notificationData)) {

			$notificationData = $this->fetchNotificationDataFromRequest();
			$this->setNotificationData($notificationData);
		}

		return $this->notificationData;
	}

	/**
	 * @param array $notificationData 
	 */
	public function setTransactTransactionId($notificationData)
	{
		$this->notificationData = $notificationData;
	}

	/**
	 * @return array
	 */
	protected function fetchNotificationDataFromRequest()
	{
		$request = $this->getRequest();

		if ( ! ($request instanceof HttpRequest)) {
			throw new Exception\RuntimeException('Do not know how to fetch Transact notification data from "' . get_class($request) . '" type of request.');
		}

		$notificationData = $request->getPost()->getArrayCopy();

		return $notificationData;
	}

	/**
	 * @return string
	 */
	protected function getTransactTransactionId()
	{
		$notificationData = $this->getNotificationData();

		if (empty($notificationData[self::REQUEST_KEY_TRANSACT_TRANSACTION_ID])) {
			throw new Execption\RuntimeException('Could not get Transact transaction id from notification data.');
		}

		$transactTransactionId = $notificationData[self::REQUEST_KEY_TRANSACT_TRANSACTION_ID];

		return $transactTransactionId;
	}

	/**
	 * @return string
	 */
	protected function getMerchantTransactionId()
	{
		$notificationData = $this->getNotificationData();

		if (empty($notificationData[self::REQUEST_KEY_MERCHANT_TRANSACTION_ID])) {
			throw new Execption\RuntimeException('Could not get merchant transaction id from notification data.');
		}

		$transactTransactionId = $notificationData[self::REQUEST_KEY_MERCHANT_TRANSACTION_ID];

		return $transactTransactionId;
	}

	/**
	 * @return Order\Order
	 */
	public function getOrder()
	{
		if (empty($this->order)) {

			$order = $this->fetchOrderFromRequest();
			$this->setOrder($order);
		}

		return $this->order;
	}

	/**
	 * @return Order\Order
	 */
	protected function fetchOrderFromRequest()
	{
		$paymentProvider = $this->getPaymentProvider();

		$merchantTransactionId = $this->getMerchantTransactionId();

		$order = $paymentProvider->getOrderFromMerchantTransactionId($merchantTransactionId);

		if (empty($order)) {
			throw new Exception\RuntimeException('Could not fetch order from request.');
		}

		return $order;
	}

	/**
	 * @param Order\Order $order 
	 */
	public function setOrder(Order\Order $order)
	{
		$this->order = $order;
	}

	/**
	 * @param array $notificationData 
	 */
	public function setNotificationData($notificationData)
	{
		$this->notificationData = $notificationData;
	}

	public function execute()
	{
		$order = $this->getOrder();

		if ($order instanceof Order\ShopOrder) {
			$this->processShopOrder($order);
		} else if ($order instanceof Order\RecurringOrder) {
			$this->processRecurringOrder($order);
		} else {
			throw new Exception\RuntimeException('Do not know what to do with notification for "' . get_class($order) . '" order type.');
		}
	}

	/**
	 * @param Order\ShopOrder $order 
	 */
	protected function processShopOrder(Order\ShopOrder $order)
	{
		$orderProvider = $this->getOrderProvider();
		$paymentProvider = $this->getPaymentProvider();

		$transaction = $order->getTransaction();

		$transactionStatus = $paymentProvider->getTransactTransactionStatus($transaction);
		$order->addToPaymentEntityParameters(Transact\PaymentProvider::PHASE_NAME_STATUS_ON_NOTIFICATION, $transactionStatus);

		$paymentProvider->updateShopOrderStatus($order, $transactionStatus);

		$orderProvider->store($order);
	}

	/**
	 * @param Order\RecurringOrder $order 
	 */
	protected function processRecurringOrder(Order\RecurringOrder $order)
	{
		$orderProvider = $this->getOrderProvider();
		/* @var $paymentProvider Tranasact\PaymentProvider */
		$paymentProvider = $this->getPaymentProvider();

		$recurringPayment = $order->getRecurringPayment();

		$transactTransactionId = $this->getTransactTransactionId();

		$lastTransaction = $recurringPayment->getLastTransaction();
		$lastTransactTransactionId = $paymentProvider->getTransactTransactionIdFromPaymentEntity($lastTransaction);

		if ($lastTransactTransactionId != $transactTransactionId) {
			throw new Exception\RuntimeException('Received notification is not for last transaction for this recurring payment.');
		}

		$transactionStatus = $paymentProvider->getTransactTransactionStatus($lastTransaction);
		$order->addToPaymentEntityParameters(Transact\PaymentProvider::PHASE_NAME_STATUS_ON_NOTIFICATION, $transactionStatus);

		$paymentProvider->updateRecurringOrderStatus($order, $transactionStatus);

		$orderProvider->store($order);
	}

	/**
	 * @return ProviderNotificationEventArgs 
	 */
	protected function getProviderNotificationEventArgs()
	{
		$order = $this->getOrder();
		$notificationData = $this->getNotificationData();

		$eventArgs = new ProviderNotificationEventArgs($this);
		$eventArgs->setOrder($order);
		$eventArgs->setNotificationData($notificationData);

		return $eventArgs;
	}

}

