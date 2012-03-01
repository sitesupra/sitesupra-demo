<?php

namespace Project\Payment\Transact\Action;

use Project\Payment\Transact;
use Supra\Payment\Action\CustomerReturnActionAbstraction;
use Supra\Payment\Transaction\TransactionStatus;
use Supra\Payment\Transaction\TransactionProvider;
use Supra\Payment\Entity\Transaction\TransactionParameter;
use Supra\Payment\Entity\Transaction\Transaction;
use Supra\Payment\Entity\RecurringPayment\RecurringPayment;
use Supra\Payment\Entity\Abstraction\PaymentEntity;
use Supra\Payment\Entity\Order;
use Supra\Payment\Order\OrderStatus;
use Supra\Payment\Order\OrderProvider;
use Supra\Payment\Order\RecurringOrderStatus;
use Supra\Payment\PaymentEntityProvider;
use Supra\Payment\Provider\Event\CustomerReturnEventArgs;
use Supra\Payment\RecurringPayment\RecurringPaymentStatus;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Payment\Entity\Abstraction\PaymentEntityParameter;
use Supra\Payment\Provider\PaymentProviderAbstraction;

class CustomerReturnAction extends CustomerReturnActionAbstraction
{

	/**
	 * @var Order\Order
	 */
	protected $order;

	/**
	 * @return Transact\PaymentProvider
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
				->getParameter(Transact\PaymentProvider::KEY_NAME_MERCHANT_TRANSACTION_ID);

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
			throw new Transact\Exception\RuntimeException('Do not know what to do with "' . get_class($order) . '" order');
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
		$order->addToPaymentEntityParameters(Transact\PaymentProvider::PHASE_NAME_STATUS_ON_RETURN, $transactionStatus);

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

		$this->returnToShop($order);
	}

	/**
	 * @param Order\RecurringOrder $order 
	 */
	protected function processRecurringOrder(Order\RecurringOrder $order)
	{
		$orderProvider = $this->getOrderProvider();

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

		$eventArgs = new CustomerReturnEventArgs();
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
