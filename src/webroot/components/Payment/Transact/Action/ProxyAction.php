<?php

namespace Project\Payment\Transact\Action;

use Project\Payment\Transact;
use Supra\Payment\Provider\PaymentProviderAbstraction;
use Supra\Payment\Order\OrderProvider;
use Supra\Payment\Order\OrderStatus;
use Supra\Payment\Entity\Order;
use Supra\Payment\Order\RecurringOrderStatus;
use Supra\Payment\Transaction\TransactionProvider;
use Supra\Payment\Transaction\TransactionStatus;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Payment\Provider\Event\ProxyEventArgs;
use Supra\Payment\RecurringPayment\RecurringPaymentStatus;
use Supra\Response\HttpResponse;
use Supra\Request\HttpRequest;
use Supra\Payment\Action\ProxyActionAbstraction;

class ProxyAction extends ProxyActionAbstraction
{
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
	 * @return Transact\PaymentProvider
	 */
	protected function getPaymentProvider()
	{
		return parent::getPaymentProvider();
	}

	public function execute()
	{
		$request = $this->getRequest();

		$orderId = $request->getParameter(PaymentProviderAbstraction::REQUEST_KEY_ORDER_ID, null);

		$order = $this->fetchOrder($orderId);
		$this->setOrder($order);

		if ($order instanceof Order\ShopOrder) {

			$this->executeShopOrderProxyAction();
		} else if ($order instanceof Order\RecurringOrder) {

			$this->executeRecurringOrderProxyAction();
		} else {
			throw new Exception\RuntimeException('Could not determine order type.');
		}
	}

	public function executeShopOrderProxyAction()
	{
		$response = $this->getResponse();
		if ( ! ($response instanceof HttpResponse)) {
			throw new Exception\RuntimeException('Do not know how to handle "' . get_class($response) . '" type of response.');
		}

		$request = $this->getRequest();
		if ( ! ($request instanceof HttpRequest)) {
			throw new Exception\RuntimeException('Do not know how to handle "' . get_class($request) . '" type of request.');
		}
		$postData = $request->getPost()->getArrayCopy();

		$orderProvider = $this->getOrderProvider();

		$paymentProvider = $this->getPaymentProvider();

		$order = $this->getOrder();
		/* @var $order Order\ShopOrder */

		// Check if arrived here from shop or from data form.
		if ( ! $request->getQuery()->has(self::REQUEST_KEY_RETURN_FROM_FORM)) {

			// If from shop, redirect user to data form URL.

			$formDataUrl = $paymentProvider->getFormDataUrl($order);
			$response->redirect($formDataUrl);
		} else {

			// If arived here from POST with CC data, begin payment 
			// process.

			$this->processShopOrderFormData($postData);
		}

		$orderProvider->store($order);
	}

	/**
	 * @param array $postData 
	 */
	protected function processShopOrderFormData($postData)
	{
		$response = $this->getResponse();

		$paymentProvider = $this->getPaymentProvider();
		$orderProvider = $this->getOrderProvider();
		$order = $this->getOrder();

		// Mark order payment as started.
		$order->setStatus(OrderStatus::PAYMENT_STARTED);
		$orderProvider->store($order);

		// Intialize Transact transaction with supplied post data.
		$initializationResult = $this->initializeTransaction($postData);

		// Store initialization result into order's payment entities parameters.
		$order->addToPaymentEntityParameters(Transact\PaymentProvider::PHASE_NAME_INITIALIZE_TRANSACTION, $initializationResult);
		$orderProvider->store($order);

		// If transaction initalization had errors, throw exception.
		if ( ! empty($initializationResult['ERROR'])) {
			$this->throwPaymentStartErrorException($order, 'Error initializing Transact transaction.');
		}

		// Check if account has "gateway collects" mode enabled...
		if ($paymentProvider->getGatewayCollects()) {

			// ... if so, redirect user to data entry URL provided by Transact.

			if (empty($initializationResult['RedirectOnsite'])) {
				$this->throwPaymentStartErrorException($order, 'Gateway collection mode enabled, but no RedirectOnsite not received.');
			}

			$redirectUrl = $initializationResult['RedirectOnsite'];

			$response->redirect($redirectUrl);
		} else {

			$chargeResult = $this->chargeTransaction($postData);

			$order->addToPaymentEntityParameters(Transact\PaymentProvider::PHASE_NAME_CHARGE_TRANSACTION, $chargeResult);
			$orderProvider->store($order);

			// If charge result has key "Redirect", it means card used was 
			// 3D-enabled and we have to redirect user to 3D provider to 
			// continue.
			if ( ! empty($chargeResult['Redirect'])) {

				$redirectUrl = trim($chargeResult['Redirect']);

				$response->redirect($redirectUrl);
			} else
				
			// If charge result has key "Status", it means card was not 
			// 3D-enabled and we have transaction result right away.
			if ( ! empty($chargeResult['Status'])) {

				switch ($chargeResult['Status']) {

					case 'success': {
							$this->onSuccess();
						} break;

					case 'failed': {
							$this->onFailure();
						} break;

					case 'pending': {
							$this->onPending();
						}break;
				}
			}
		}
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

	protected function initializeTransaction($postData)
	{
		$order = $this->getOrder();
		$paymentProvider = $this->getPaymentProvider();

		$result = $paymentProvider->initializeTransaction($order, $postData);

		return $result;
	}

	protected function chargeTransaction($postData)
	{
		$order = $this->getOrder();

		$paymentProvider = $this->getPaymentProvider();

		$result = $paymentProvider->chargeTransaction($order, $postData);

		return $result;
	}

	public function executeRecurringOrderProxyAction()
	{
//		$orderProvider = $this->getOrderProvider();
//		$paymentProvider = $this->getPaymentProvider();
//
//		$order = $this->getOrder();
//		/* @var $order Order\RecurringOrder */
//
//		$order->setStatus(OrderStatus::PAYMENT_STARTED);
//
//		$orderProvider->store($order);
	}

	/**
	 * @return ProxyEventArgs 
	 */
	protected function getProxtyEventArgs()
	{
		$args = new ProxyEventArgs();

		$order = $this->getOrder();

		$args->setOrder($order);

		return $args;
	}

	protected function onSuccess()
	{

		$orderProvider = $this->getOrderProvider();

		$order = $this->getOrder();

		if ($order instanceof Order\ShopOrder) {

			$transaction = $order->getTransaction();

			$transaction->setStatus(TransactionStatus::SUCCESS);
		} else if ($order instanceof Order\RecurringOrder) {

			throw new Exception\RuntimeException('Recurring order processing is not implemeted yet.');
		}

		$orderProvider->store($order);
	}

	protected function onFailure()
	{
		$orderProvider = $this->getOrderProvider();

		$order = $this->getOrder();

		if ($order instanceof Order\ShopOrder) {

			$transaction = $order->getTransaction();

			$transaction->setStatus(TransactionStatus::FAILED);
		} else if ($order instanceof Order\RecurringOrder) {

			throw new Exception\RuntimeException('Recurring order processing is not implemeted yet.');
		}

		$orderProvider->store($order);
	}

	protected function onPending()
	{
		$orderProvider = $this->getOrderProvider();

		$order = $this->getOrder();

		if ($order instanceof Order\ShopOrder) {

			$transaction = $order->getTransaction();

			$transaction->setStatus(TransactionStatus::SYSTEM_ERROR);
		} else if ($order instanceof Order\RecurringOrder) {

			throw new Exception\RuntimeException('Recurring order processing is not implemeted yet.');
		}

		$orderProvider->store($order);

		throw new Exception\RuntimeException('Pending Transact tranasctions not implemented yet.');
	}

}
