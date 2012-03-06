<?php

namespace Project\Payment\Transact\Action;

use Project\Payment\Transact;
use Supra\Request\HttpRequest;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Payment\Transaction\TransactionStatus;
use Supra\Payment\RecurringPayment\RecurringPaymentStatus;
use Supra\Payment\Provider\Event\ProviderNotificationEventArgs;
use Supra\Payment\Provider\PaymentProviderAbstraction;
use Supra\Payment\Order\OrderStatus;
use Supra\Payment\Order\RecurringOrderStatus;
use Supra\Payment\Entity\TransactionParameter;
use Supra\Payment\Entity\Order;
use Supra\Payment\Entity\RecurringPayment\RecurringPaymentTransaction;
use Supra\Payment\Action\ProviderNotificationActionAbstraction;
use Supra\Payment\Entity\Transaction\Transaction;

class ProviderNotificationAction extends ProviderNotificationActionAbstraction
{
	const REQUEST_KEY_TRANSACT_TRANSACTION_ID = 'ID';

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

		$transactTransactionId = $this->getTransactTransactionId();

		$order = $paymentProvider->getOrderFromTransactTransactionId($transactTransactionId);

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

		$transactionStatus = $paymentProvider->getTransactionStatus($order);
		$order->addToPaymentEntityParameters(Transact\PaymentProvider::PHASE_NAME_STATUS_ON_NOTIFICATION, $transactionStatus);

		if (empty($transactionStatus) || empty($transactionStatus['Status'])) {
			throw new Exception\RuntimeException('Could not get transaction status.');
		}

		switch ($transactionStatus['Status']) {

			case 'Success': {
					$order->getTransaction()
							->setStatus(TransactionStatus::SUCCESS);
				} break;

			case 'Failed': {
					$order->getTransaction()
							->setStatus(TransactionStatus::FAILED);
				} break;

			case 'Pending': {
					throw new Exception\RuntimeException('Pending transaction handling not impleneted yet.');
				} break;

			default: {
					throw new Exception\RuntimeException('Transaction status "' . $transactionStatus['Status'] . '" is not recognized.');
				}
		}

		$orderProvider->store($order);
	}

	/**
	 * @param Order\RecurringOrder $order 
	 */
	protected function processRecurringOrder(Order\RecurringOrder $order)
	{
		throw new Exception\RuntimeException('Handling of notifications for recurring orders not implemented yet.');
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

