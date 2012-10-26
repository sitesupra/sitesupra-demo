<?php

namespace Project\Payment\Dengi\Action;

use Project\Payment\Dengi;
use Project\Payment\Dengi\Exception;
use Supra\Payment\Action\CustomerReturnActionAbstraction;
use Supra\Payment\Transaction\TransactionStatus;
use Supra\Payment\Transaction\TransactionProvider;
use Supra\Payment\Entity\Transaction\TransactionParameter;
use Supra\Payment\Entity\Transaction\Transaction;
use Supra\Payment\Entity\RecurringPayment\RecurringPayment;
use Supra\Payment\Entity\Abstraction\PaymentEntity;
use Supra\Payment\Entity\Abstraction\PaymentEntityParameter;
use Supra\Payment\Entity\Order;
use Supra\Payment\Order\OrderStatus;
use Supra\Payment\Order\OrderProvider;
use Supra\Payment\Order\RecurringOrderStatus;
use Supra\Payment\PaymentEntityProvider;
use Supra\Payment\Provider\Event\CustomerReturnEventArgs;
use Supra\Payment\Provider\PaymentProviderAbstraction;
use Supra\Payment\RecurringPayment\RecurringPaymentStatus;
use Supra\ObjectRepository\ObjectRepository;

class CustomerReturnAction extends CustomerReturnActionAbstraction
{

	/**
	 * @var Order\Order
	 */
	protected $order;

	/**
	 * @var \Supra\Request\RequestData
	 */
	protected $parameters;

	/**
	 * @return \Supra\Request\RequestData
	 */
	public function getParameters()
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
	 * @return Dengi\PaymentProvider
	 */
	protected function getPaymentProvider()
	{
		return parent::getPaymentProvider();
	}

	/**
	 * @return Order\Order
	 */
	protected function getOrder()
	{
		if (empty($this->order)) {

			$order = $this->fetchOrderFromRequest();

			$this->setOrder($order);
		}

		return $this->order;
	}

	/**
	 * @param Order\Order $order 
	 */
	protected function setOrder(Order\Order $order)
	{
		$this->order = $order;
	}

	/**
	 * @return Order\Order
	 */
	protected function fetchOrderFromRequest()
	{
		$dengiOrderId = $this->getParameters()->get('order_id');

		$orderProvider = $this->getOrderProvider();

		$order = $orderProvider->getOrder($dengiOrderId);

		return $order;
	}

	/**
	 * @throws Dengi\Exception\RuntimeException
	 */
	public function execute()
	{
		$parameters = $this->getParameters();

		$paymentProvider = $this->getPaymentProvider();

		if ( ! $paymentProvider->validateDolSign($parameters)) {

			throw new Dengi\Exception\RuntimeException('Bad request, DOL_SIGN is not valid.');
		}

		$order = $this->getOrder();

		if ($order instanceof Order\ShopOrder) {

			$this->handleShopOrder($order);
		} else {

			throw new Dengi\Exception\RuntimeException('Do not know what to do with "' . get_class($order) . '" order');
		}
	}

	/**
	 * @param Order\ShopOrder $order
	 */
	protected function processShopOrder(Order\ShopOrder $order)
	{
		$orderProvider = $this->getOrderProvider();

		$transaction = $order->getTransaction();

		$parameters = $this->getParameters();

		$transaction->addToParameters(Dengi\PaymentProvider::PHASE_NAME_STATUS_ON_RETURN, $parameters);

		$request = $this->getRequest();
		list($action) = $request->getActions(1);

		if ($action == Dengi\RequestController::DENGI_FAILURE) {
			$transaction->setStatus(TransactionStatus::FAILED);
		} else {
			//
		}

		$orderProvider->store($order);

		$this->returnToShop($order);
	}

	/**
	 * @return CustomerReturnEventArgs 
	 */
	protected function getCustomerReturnEventArgs()
	{
		$order = $this->getOrder();
		$response = $this->getResponse();

		$eventArgs = new CustomerReturnEventArgs($this);
		$eventArgs->setOrder($order);
		$eventArgs->setResponse($response);

		return $eventArgs;
	}

	/**
	 * @param Order\Order $order 
	 */
	private function returnToShop(Order\Order $order)
	{
		$initiatorUrl = $order->getInitiatorUrl();

		$this->returnToPaymentInitiator($initiatorUrl, array(Dengi\PaymentProvider::REQUEST_KEY_ORDER_ID => $order->getId()));
	}

	/**
	 * @param \Supra\Payment\Entity\Order\RecurringOrder $order
	 * @throws Dengi\Exception\RuntimeException
	 */
	protected function processRecurringOrder(Order\RecurringOrder $order)
	{
		throw new Dengi\Exception\RuntimeException('Recurring orders not supported.');
	}

}
