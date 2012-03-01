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

	/**
	 * @var Order\Order
	 */
	protected $order;

	/**
	 * @var array
	 */
	protected $notificationData;

	/**
	 * @return Order\Order
	 */
	public function getOrder()
	{
		if (empty($this->order)) {
			throw new Transact\Exception\RuntimeException('Order is not set.');
		}

		return $this->order;
	}

	/**
	 * @param Order\Order $order 
	 */
	public function setOrder(Order\Order $order)
	{
		$this->order = $order;
	}

	/**
	 * @return array
	 */
	public function getNotificationData()
	{
		return $this->notificationData;
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
		$request = $this->getRequest();
		/* @var $request HttpRequest */
		$notificationData = $request->getPost()->getArrayCopy();

		\Log::debug('AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA:', $notificationData);
		\Log::debug('AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA:', $notificationData);
		\Log::debug('AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA:', $notificationData);
		\Log::debug('AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA:', $notificationData);
		\Log::debug('AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA:', $notificationData);
		
		$this->setNotificationData($notificationData);

	}

	protected function processShopOrder(Order\ShopOrder $order)
	{
		$orderProvider = $this->getOrderProvider();

		$orderProvider->store($order);
	}

	protected function processRecurringOrder(Order\RecurringOrder $order)
	{
		
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

