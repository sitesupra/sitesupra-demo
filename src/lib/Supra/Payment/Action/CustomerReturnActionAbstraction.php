<?php

namespace Supra\Payment\Action;

use Supra\Payment\Provider\Event\CustomerReturnEventArgs;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Payment\Entity\Order\Order;
use Supra\Payment\Order\OrderProvider;
use Supra\Payment\Order\OrderStatus;
use Supra\Payment\Transaction\TransactionStatus;
use Supra\Payment\Entity\Transaction\Transaction;
use Supra\Response\HttpResponse;
use Supra\Payment\Transaction\TransactionProvider;
use Supra\Payment\Provider\PaymentProviderAbstraction;

abstract class CustomerReturnActionAbstraction extends ActionAbstraction
{

	/**
	 * @var Order
	 */
	protected $order;

	/**
	 * @var OrderProvide
	 */
	protected $orderProvider;

	/**
	 * @var TransactionProvider
	 */
	protected $transactionProvider;

	/**
	 * @var PaymentProviderAbstraction
	 */
	protected $paymentProvider;

	/**
	 * @return Transaction
	 */
	abstract public function getTransactionFromRequest();

	abstract public function processCustomerReturn();

	public function execute()
	{
		$this->orderProvider = new OrderProvider();
		$this->transactionProvider = new TransactionProvider();
		
		//\Log::debug('$this->transctionProvider: ', $this->transctionProvider);

		$transaction = $this->getTransactionFromRequest();
		
		$this->paymentProvider = $this->transactionProvider->getTransactionPaymentProvider($transaction);

		$this->order = $this->orderProvider->getOrderByTransaction($transaction);

		$statusBefore = $this->order->getStatus();

		$this->processCustomerReturn();

		$statusNow = $this->order->getStatus();

		\Log::debug('Customer Return Abstraction ', $statusBefore, ' ---> ', $statusNow);

		try {

			if ($statusBefore == OrderStatus::PAYMENT_STARTED) {

				if ($statusNow == OrderStatus::PAYMENT_RECEIVED) {
					$this->paymentReceived();
				}
				if ($statusNow == OrderStatus::PAYMENT_PENDING) {
					$this->paymentPending();
				}
				else if ($statusNow == OrderStatus::PAYMENT_CANCELED) {
					$this->paymentCanceled();
				}
				else if ($statusNow == OrderStatus::PAYMENT_FAILED) {
					$this->paymentFailed();
				}
			}
			else if ($statusBefore == OrderStatus::PAYMENT_RECEIVED) {
				$this->fireProviderNotificationEvent();
			}
		}
		catch (Exception\RuntimeException $e) {

			$this->order->setStatus(OrderStatus::SYSTEM_ERROR);
			$this->systemError();
		}

		$this->orderProvider->store($this->order);

		$response = $this->getResponse();

		if ($response instanceof HttpResponse) {

			if ( ! $response->isRedirect()) {

				$queryParameters = array(
						PaymentProviderAbstraction::ORDER_ID => $this->order->getId()
				);

				$queryParts = parse_url($this->order->getReturnUrl());

				$urlBase = $queryParts['scheme'] . '://' . $queryParts['host'] . $queryParts['path'];

				$query = array();

				if ( ! empty($queryParts['query'])) {
					$query[] = $queryParts['query'];
				}
				$query[] = http_build_query($queryParameters);

				$query = join('&', $query);

				$response->redirect($urlBase . '?' . $query);
			}
		}
	}

	protected function fireCustomerReturnEvent()
	{
		$eventManager = ObjectRepository::getEventManager($this);

		$eventArgs = new CustomerReturnEventArgs();
		$eventArgs->setOrder($this->order);
		$eventArgs->setResponse($this->response);

		$eventManager->fire(PaymentProviderAbstraction::EVENT_CUSTOMER_RETURN, $eventArgs);
	}

	protected function paymentReceived()
	{
		$this->fireCustomerReturnEvent();
	}

	protected function paymentPending()
	{
		$this->fireCustomerReturnEvent();
	}

	protected function paymentCanceled()
	{
		$this->fireCustomerReturnEvent();
	}

	protected function paymentFailed()
	{
		$this->fireCustomerReturnEvent();
	}

	protected function systemError()
	{
		$this->fireCustomerReturnEvent();
	}

}
