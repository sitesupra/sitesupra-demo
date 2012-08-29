<?php

namespace Project\Payment\Dengi\Action;

use Project\Payment\Transact;
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
	 * @return Order\Order
	 */
	protected function fetchOrderFromRequest()
	{
		$merchantTransactionId = $this->getRequest()
				->getParameter(Dengi\PaymentProvider::KEY_NAME_MERCHANT_TRANSACTION_ID);

		$paymentProvider = $this->getPaymentProvider();

		$order = $paymentProvider->getOrderFromMerchantTransactionId($merchantTransactionId);

		return $order;
	}

	/**
	 * @param Order\Order $order 
	 */
	protected function setOrder(Order\Order $order)
	{
		$this->order = $order;
	}

	public function execute()
	{
		$order = $this->getOrder();

		if ($order instanceof Order\ShopOrder) {
			$this->handleShopOrder($order);
		} else if ($order instanceof Order\RecurringOrder) {
			$this->handleRecurringOrder($order);
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

		$paymentProvider = $this->getPaymentProvider();

		$transaction = $order->getTransaction();

		$transactionStatus = $paymentProvider->getTransactTransactionStatus($transaction);
		$transaction->addToParameters(Dengi\PaymentProvider::PHASE_NAME_STATUS_ON_RETURN, $transactionStatus);

		$paymentProvider->updateShopOrderStatus($order, $transactionStatus);

		$orderProvider->store($order);

		$this->returnToShop($order);
	}

	/**
	 * @param Order\RecurringOrder $order 
	 */
	protected function processRecurringOrder(Order\RecurringOrder $order)
	{
		$orderProvider = $this->getOrderProvider();

		$paymentProvider = $this->getPaymentProvider();

		$recurringPayment = $order->getRecurringPayment();

		$lastTransaction = $recurringPayment->getLastTransaction();
		$initialTransaction = $recurringPayment->getInitialTransaction();

		if ($lastTransaction->getId() != $initialTransaction->getId()) {
			throw new Exception\RuntimeException('Recurring payment transaction is not initial for this recurring .');
		}

		$transactionStatus = $paymentProvider->getTransactTransactionStatus($lastTransaction);
		$lastTransaction->addToParameters(Dengi\PaymentProvider::PHASE_NAME_STATUS_ON_RETURN, $transactionStatus);

		$paymentProvider->updateRecurringOrderStatus($order, $transactionStatus);

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

		$this->returnToPaymentInitiator($initiatorUrl);
	}

}
