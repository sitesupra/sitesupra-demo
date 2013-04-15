<?php

namespace Project\Payment\Dengi\Action;

use Project\Payment\Dengi;
use Supra\Payment\Provider\PaymentProviderAbstraction;
use Supra\Payment\Order\OrderProvider;
use Supra\Payment\Order\OrderStatus;
use Supra\Payment\Entity\Order;
use Supra\Payment\Transaction\TransactionProvider;
use Supra\Payment\Transaction\TransactionStatus;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Payment\Provider\Event\ProxyEventArgs;
use Supra\Response\HttpResponse;
use Supra\Request\HttpRequest;
use Supra\Payment\Action\ProxyActionAbstraction;
use Project\Payment\Dengi\Exception;

class ProxyAction extends ProxyActionAbstraction
{

	const SUCCESS = 'success';
	const PENDING = 'pending';
	const FAILED = 'failed';
	const REQUEST_KEY_RETURN_FROM_FORM = 'returnFromForm';

	/**
	 * @var Order\Order
	 */
	protected $order;

	/**
	 * @return Order\Order
	 */
	public function getOrder()
	{
		if (empty($this->order)) {
			throw new Exception\RuntimeException('Order not set.');
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
	 * @return Dengi\PaymentProvider
	 */
	protected function getPaymentProvider()
	{
		return parent::getPaymentProvider();
	}

	/**
	 * @throws Exception\RuntimeException 
	 */
	public function execute()
	{
		$response = $this->getResponse();
		if ( ! ($response instanceof HttpResponse)) {
			throw new Exception\RuntimeException('Do not know how to handle "' . get_class($response) . '" type of response.');
		}

		$request = $this->getRequest();
		if ( ! ($request instanceof HttpRequest)) {
			throw new Exception\RuntimeException('Do not know how to handle "' . get_class($request) . '" type of request.');
		}

		$orderId = $request->getParameter(PaymentProviderAbstraction::REQUEST_KEY_ORDER_ID, null);

		$order = $this->fetchOrder($orderId);
		$this->setOrder($order);

		if ($order instanceof Order\ShopOrder) {

			$this->executeShopOrderProxyAction();
		} else if ($order instanceof Order\RecurringOrder) {

			throw new Exception\RuntimeException('Recurring orders not supported.');
		} else {
			throw new Exception\RuntimeException('Could not determine order type.');
		}

		$this->fireProxyEvent();
	}

	/**
	 * 
	 */
	public function executeShopOrderProxyAction()
	{
		$response = $this->getResponse();
		$request = $this->getRequest();

		$postData = $request->getPost()->getArrayCopy();

		$orderProvider = $this->getOrderProvider();

		$paymentProvider = $this->getPaymentProvider();

		$order = $this->getOrder();
		/* @var $order Order\ShopOrder */

		// Check if arrived here from shop or from data form.
		if ( ! $request->getQuery()->has(self::REQUEST_KEY_RETURN_FROM_FORM)) {

			// If from shop, redirect user to data form URL.

			$dataFormUrl = $paymentProvider->getDataFormUrl($order);
			$response->redirect($dataFormUrl);
		} else {

			// If arived here from POST with CC data - validate data and 
			// begin payment process.

			if ($this->validateShopOrderFormData($postData)) {
				$this->processShopOrderFormData($postData);
			} else {

				$dataFormUrl = $paymentProvider->getDataFormUrl($order);
				$response->redirect($dataFormUrl);
			}
		}

		$orderProvider->store($order);
	}

	/**
	 * @param array $formData 
	 */
	protected function processShopOrderFormData($formData)
	{
		$paymentProvider = $this->getPaymentProvider();
		$orderProvider = $this->getOrderProvider();
		$order = $this->getOrder();

		// Mark order payment as started.
		$order->setStatus(OrderStatus::PAYMENT_STARTED);

		$orderProvider->store($order);

		// Intialize Transact transaction with supplied post data.
		$orderProvider->store($order);

		$backend = $paymentProvider->getBackend($formData['mode_type']);

		$backend->setPaymentProvider($paymentProvider);
		$backend->setOrder($order);

		$order->addToPaymentEntityParameters(Dengi\PaymentProvider::PHASE_NAME_INITIALIZE_TRANSACTION, $formData);
		
		$status = $backend->proxyAction($formData, $this->getResponse());

		switch ($status) {

			case self::SUCCESS: {
					$this->onSuccess();
				} break;

			case self::FAILED: {
					$this->onFailure();
				} break;

			case self::PENDING: {
					$this->onPending();
				} break;
		}
	}

	/**
	 * @param array $formData
	 * @return boolean 
	 */
	private function validateShopOrderFormData($formData)
	{
		$paymentProvider = $this->getPaymentProvider();
		$amount = null;

		$providerBackend = $paymentProvider->getBackend($formData['mode_type']);
		
		if (isset($formData['amount'])) {
			$amount = 0;
			if ($formData['amount'] > 0) {
				$amount = $formData['amount'];
			}
		}

		$errorMessages = $providerBackend->validateForm($formData);

		if ( ! empty($errorMessages) || (!is_null($amount) && $amount == 0)) {

			$order = $this->getOrder();

			$session = $paymentProvider->getSessionForOrder($order);
			$session->errorMessages = $errorMessages;

			return false;
		}
		
		if ($amount > 0) {
			$order = $this->getOrder();
			/* @var $order \Supra\Payment\Entity\Order\ShopOrder */
			$items = $order->getItems();
			$orderItem = $items->get(0);
			
			/* @var $orderItem \Supra\Payment\Entity\Order\OrderProductItem */
			if (!($orderItem instanceof \Supra\Payment\Entity\Order\OrderProductItem)) {
				throw new Exception\RuntimeException('OrderItem item is not an instance of OrderProductItem');
			}
			
			$orderItem->setPrice($amount);
			
			$transaction = $order->getTransaction();
			$transaction->setAmount($amount);
			
			
			$em = ObjectRepository::getEntityManager($this);
			$em->persist($order);
			
			$or = $em->getRepository(\Project\Entity\Operation\OperationAddFunds::CN());
			$operation = $or->findOneBy(array(
				'paymentOrder' => $order->getId()
			));
			
			if ($operation instanceof \Project\Entity\Operation\OperationAddFunds) {
				/* @var $operation \Project\Entity\Operation\OperationAddFunds */
				$operation->setAmount($amount);
				$em->persist($operation);
			}
			
			$em->flush();
			
			$this->setOrder($order);
		}

		return true;
	}

	/**
	 * @param Order\Order $order
	 * @param string $message 
	 * @throws Exception\RuntimeException
	 */
	private function throwPaymentStartErrorException(Order\Order $order, $message)
	{

		$orderProvider = $this->getOrderProvider();

		$order->setStatus(OrderStatus::PAYMENT_START_ERROR);
		$orderProvider->store($order);

		throw new Exception\RuntimeException($message);
	}

	/**
	 * @return ProxyEventArgs 
	 */
	protected function getProxyEventArgs()
	{
		$args = new ProxyEventArgs($this);

		$order = $this->getOrder();

		$args->setOrder($order);

		return $args;
	}

	/**
	 * @throws Exception\RuntimeException
	 */
	protected function onSuccess()
	{
		$orderProvider = $this->getOrderProvider();

		$order = $this->getOrder();

		if ($order instanceof Order\ShopOrder) {

			$transaction = $order->getTransaction();

			$transaction->setStatus(TransactionStatus::SUCCESS);
		} else if ($order instanceof Order\RecurringOrder) {

			throw new Exception\RuntimeException('Recurring orders not supported.');
		}

		$orderProvider->store($order);
	}

	/**
	 * @throws Exception\RuntimeException
	 */
	protected function onFailure()
	{
		$orderProvider = $this->getOrderProvider();

		$order = $this->getOrder();

		if ($order instanceof Order\ShopOrder) {

			$transaction = $order->getTransaction();

			$transaction->setStatus(TransactionStatus::FAILED);
		} else if ($order instanceof Order\RecurringOrder) {

			throw new Exception\RuntimeException('Recurring orders not supported.');
		}

		$orderProvider->store($order);

		$this->returnToPaymentInitiator($order->getInitiatorUrl());
	}

	/**
	 * @throws Exception\RuntimeException
	 */
	protected function onPending()
	{
		$orderProvider = $this->getOrderProvider();

		$order = $this->getOrder();

		if ($order instanceof Order\ShopOrder) {

			$transaction = $order->getTransaction();

			$transaction->setStatus(TransactionStatus::PENDING);
		} else if ($order instanceof Order\RecurringOrder) {

			throw new Exception\RuntimeException('Recurring orders not supported.');
		}

		$orderProvider->store($order);
	}

}