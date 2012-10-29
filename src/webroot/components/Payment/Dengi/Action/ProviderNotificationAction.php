<?php

namespace Project\Payment\Dengi\Action;

/*
  http://playpad.videinfra.com/payment/status-change?amount=93.59&userid=00ah52mrv04ok84gos4k&userid_extra=&orderid=00be66tub01gcsw00o4w&paymentid=102957604&paymode=40&key=2e19fea701ee34cbeb02c3d03ae6cc2d
  http://playpad.videinfra.com/payment/status-change?userid=00ah52mrv04ok84gos4k&userid_extra=&key=65d480b7c0f40dd2983a2984eefc8e02
 */

use Project\Payment\Dengi;
use Project\Payment\Dengi\Exception;
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

	const NOTIFICATION_TYPE_CHECK = 'check';
	const NOTIFICATION_TYPE_NOTIFY = 'notify';

	/**
	 * @var Order\Order
	 */
	protected $order;

	/**
	 * @var array
	 */
	protected $notificationData;

	/**
	 * @var \Supra\Request\RequestData
	 */
	protected $parameters;

	/**
	 * @return Dengi\PaymentProvider
	 */
	protected function getPaymentProvider()
	{
		return parent::getPaymentProvider();
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
	 * @return \Supra\Request\RequestData
	 */
	protected function getParameters()
	{
		if (empty($this->parameters)) {

			$request = $this->getRequest();

			if ($request->isPost()) {
				$this->parameters = $request->getPost();
			} else {
				$this->parameters = $request->getQuery();
			}
		}

		return $this->parameters;
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

	/**
	 * @return array
	 */
	public function getNotificationData()
	{
		return $this->notificationData;
	}

	/**
	 * @throws Exception\RuntimeException
	 */
	public function execute()
	{
		$notificationType = $this->getNotificationType();

		switch ($notificationType) {
			case self::NOTIFICATION_TYPE_CHECK: {

					$this->executeCheck();
				} break;

			case self::NOTIFICATION_TYPE_NOTIFY: {

					$order = $this->getOrder();

					if ($order instanceof Order\ShopOrder) {

						$this->handleShopOrder($order);
					}
				} break;

			default: {
					throw new Exception\RuntimeException('Notification not recognized.');
				}
		}
	}

	/**
	 * @return string
	 */
	protected function getNotificationType()
	{
		$notificationType = null;

		$parameters = $this->getParameters();

		//\Log::error('PPP: ', $parameters);

		if ($parameters->has('amount')) {
			$notificationType = self::NOTIFICATION_TYPE_NOTIFY;
		} else {
			$notificationType = self::NOTIFICATION_TYPE_CHECK;
		}

		return $notificationType;
	}

	/**
	 * 
	 * @throws Exception\RuntimeException
	 */
	protected function executeCheck()
	{
		$paymentProvider = $this->getPaymentProvider();

		$parameters = $this->getParameters();

		$dengiUserId = $parameters->get('userid');
		$receivedChecksum = $parameters->get('key');

		//$this->setNotificationData($parameters->getArrayCopy());
		//$order->addToPaymentEntityParameters(Dengi\PaymentProvider::PHASE_NAME_STATUS_ON_NOTIFICATION, $this->getNotificationData());

		$checkumValid = $paymentProvider->checkVerifyDengiOrderChecksum($dengiUserId, $receivedChecksum);

		$userExists = $this->isValidOrderUser($dengiUserId);

		$output = $paymentProvider->makeVerifyDengiOrderCheckumResponse($checkumValid && $userExists);

		$response = $this->getResponse();

		if ($response instanceof \Supra\Response\HttpResponse) {

			$response->header('Content-Type', 'text/xml');
			$response->output($output);
		}
	}

	/**
	 * @param string $dengiUserId
	 * @return boolean
	 */
	protected function isValidOrderUser($dengiUserId)
	{
		return true;
	}

	/**
	 * 
	 */
	protected function executeNotify()
	{
		$orderProvider = $this->getOrderProvider();

		$parameters = $this->getParameters();

		$paymentProvider = $this->getPaymentProvider();

		$amount = $parameters->get('amount');
		$dengiUserId = $parameters->get('userid');
		$dengiPaymentId = $parameters->get('paymentid');
		$receivedChecksum = $parameters->get('key');

		$this->setNotificationData($parameters->getArrayCopy());

		$order = $this->getOrder();

		if ( ! $order instanceof Order\ShopOrder) {
			throw new Exception\RuntimeException('Do not how to process notification for "' . get_class($order) . '" order type.');
		}
		/* @var $order Order\ShopOrder */

		$order->addToPaymentEntityParameters(Dengi\PaymentProvider::PHASE_NAME_STATUS_ON_NOTIFICATION, $this->getNotificationData());

		$notificationValid = $paymentProvider->checkDengiOrderSuccessChecksum($amount, $dengiUserId, $dengiPaymentId, $receivedChecksum);

		if ($notificationValid == false) {
			throw new Exception\RuntimeException('Notification validtation failed.');
		}

		/* @var $transaction Supra\Payment\Entity\Transaction\Transaction */

		$transaction = $order->getTransaction();

		$transaction->setStatus(TransactionStatus::SUCCESS);

		$orderProvider->store($order);

		$response = $this->getResponse();

		$notificationResponse = $paymentProvider->makeCheckDengiOrderSuccessResponse($order->getId(), $notificationValid);

		\Log::debug('$notificationResponse: ' . $notificationResponse);

		$response->output($notificationResponse);
	}

	/**
	 * @return Order\Order
	 */
	protected function fetchOrderFromRequest()
	{
		$orderId = $this->getParameters()->get('orderid');

		$orderProvider = $this->getOrderProvider();

		$order = $orderProvider->getOrder($orderId);

		return $order;
	}

	/**
	 * @param Order\ShopOrder $order 
	 */
	protected function processShopOrder(Order\ShopOrder $order)
	{
		$notificationType = $this->getNotificationType();

		if ($notificationType == self::NOTIFICATION_TYPE_NOTIFY) {

			$this->executeNotify();
		} else {

			throw new Exception\RuntimeException('Do not know what to do with this type of notification.');
		}
	}

	/**
	 * @param Order\RecurringOrder $order 
	 */
	protected function processRecurringOrder(Order\RecurringOrder $order)
	{
		throw new Exception\RuntimeException('Recurring orders are not supported.');
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

