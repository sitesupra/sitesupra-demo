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
use Supra\Payment\Entity\RecurringPayment\RecurringPayment;
use Supra\Payment\Entity\RecurringPayment\RecurringPaymentTransaction;
use Project\Payment\Transact\Exception;

class ProxyAction extends ProxyActionAbstraction
{

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

			$this->executeRecurringOrderProxyAction();
		} else {
			throw new Exception\RuntimeException('Could not determine order type.');
		}
		
		$this->fireProxyEvent();
	}

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

			$formDataUrl = $paymentProvider->getDataFormUrl($order);
			$response->redirect($formDataUrl);
		} else {

			// If arived here from POST with CC data - validate data and 
			// begin payment process.

			if ($this->validateShopOrderFormData($postData)) {
				$this->processShopOrderFormData($postData);
			} else {

				$formDataUrl = $paymentProvider->getDataFormUrl($order);
				$response->redirect($formDataUrl);
			}
		}

		$orderProvider->store($order);
	}

	/**
	 * @param array $formData 
	 */
	protected function processShopOrderFormData($formData)
	{
		$response = $this->getResponse();

		$paymentProvider = $this->getPaymentProvider();
		$orderProvider = $this->getOrderProvider();
		$order = $this->getOrder();

		// Mark order payment as started.
		$order->setStatus(OrderStatus::PAYMENT_STARTED);
		$orderProvider->store($order);
		
		// Intialize Transact transaction with supplied post data.
		$initializationResult = $this->initializeTransaction($formData);
		
		$orderProvider->store($order);

		// If transaction initalization had errors, throw exception.
		if ( ! empty($initializationResult['ERROR'])) {
			$this->throwPaymentStartErrorException($order, "Error initializing Transact transaction, got error: {$initializationResult['ERROR']}");
		}

		// Check if account has "gateway collects" mode enabled...
		if ($paymentProvider->getGatewayCollects()) {

			// ... if so, redirect user to data entry URL provided by Transact.
			// 
			// Check for "RedirectOnsite" though...
			if (empty($initializationResult['RedirectOnsite'])) {
				$this->throwPaymentStartErrorException($order, 'Gateway collection mode enabled, but no RedirectOnsite not received.');
			}

			$redirectUrl = $initializationResult['RedirectOnsite'];

			$response->redirect($redirectUrl);
		} else {

			if(! empty($initializationResult['RedirectOnsite'])) {
				$this->throwPaymentStartErrorException($order, 'Gateway collection mode disabled, but RedirectOnsite received.');
			}
			
			// ... otherwise perform charge.

			$chargeResult = $this->chargeTransaction($formData);

			$orderProvider->store($order);

			// If charge result has key "Redirect", card used was 
			// 3D-enabled and we have to redirect user to 3D provider to 
			// continue.

			if ( ! empty($chargeResult['Redirect'])) {

				$redirectUrl = trim($chargeResult['Redirect']);

				$response->redirect($redirectUrl);
			} else if ( ! empty($chargeResult['Status'])) {

				// If charge result has key "Status", it means card was not 
				// 3D-enabled and we have transaction result right away.

				switch (strtolower($chargeResult['Status'])) {

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
			} else {

				// Otherwise something has gone terribly wrong.

				$this->onFailure();
			}
		}
	}

	/**
	 * @param array $formData
	 * @return boolean 
	 */
	private function validateShopOrderFormData($formData)
	{
		$paymentProvider = $this->getPaymentProvider();

		$formInputNames = array();
		if ($paymentProvider->getGatewayCollects()) {

			$formInputNames = array(
				'name_on_card',
				'street',
				'zip',
				'city',
				'country',
				'state',
				'email',
				'phone',
			);
		} else {

			$formInputNames = array(
				'name_on_card',
				'street',
				'zip',
				'city',
				'country',
				'state',
				'email',
				'phone',
				'cc',
				'cvv',
				'expire',
//				'bin_name',
//				'bin_phone',
			);
		}

		$errorMessages = array();

		foreach ($formInputNames as $inputName) {

			if (empty($formData[$inputName])) {
				$errorMessages[] = 'Missing "' . $inputName . '"';
			}
		}


		if ( ! empty($errorMessages)) {
			
			$order = $this->getOrder();

			$session = $paymentProvider->getSessionForOrder($order);
			$session->errorMessages = $errorMessages;

			return false;
		}

		return true;
	}

	/**
	 * @param array $formData 
	 * @return boolean
	 */
	protected function validateRecurringOrderFormData($formData)
	{
		return $this->validateShopOrderFormData($formData);
	}

	/**
	 * @param array $formData 
	 */
	protected function processRecurringOrderFormData($formData)
	{
		$response = $this->getResponse();

		$paymentProvider = $this->getPaymentProvider();
		$orderProvider = $this->getOrderProvider();
		$order = $this->getOrder();
		/* @var $order Order\RecurringOrder */

		// Mark order payment as started.
		$order->setStatus(OrderStatus::PAYMENT_STARTED);

		// Intialize Transact recurring payment with supplied post data.
		$initializationResult = $paymentProvider->initializeRecurringPayment($order, $formData);

		$orderProvider->store($order);

		// If transaction initalization had errors, throw exception.
		if ( ! empty($initializationResult['ERROR'])) {
			$this->throwPaymentStartErrorException($order, "Error initializing Transact transaction, got error: {$initializationResult['ERROR']}");
		}

		// Check if account has "gateway collects" mode enabled...
		if ($paymentProvider->getGatewayCollects()) {

			// ... if so, redirect user to data entry URL provided by Transact.

			if (empty($initializationResult['RedirectOnsite'])) {
				$this->throwPaymentStartErrorException($order, 'Gateway collection mode is enabled, but no RedirectOnsite not received.');
			}

			$redirectUrl = $initializationResult['RedirectOnsite'];

			$response->redirect($redirectUrl);
		} else {

			$chargeResult = $paymentProvider->chargeInitialRecurringTransaction($order, $formData);

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

				switch (strtolower($chargeResult['Status'])) {

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
			} else {
				//\Log::debug('FFFFFFFFFFFFFFFFFFFFAIL: ', $chargeResult);
				$this->onFailure();
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

	protected function initializeRecurringPayment($postData)
	{
		$order = $this->getOrder();
		/* @var $order Order\RecurringOrder  */

		$paymentProvider = $this->getPaymentProvider();

		$initializationResult = $paymentProvider->initializeRecurrentPayment($order, $postData);

		return $initializationResult;
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
		$response = $this->getResponse();
		$request = $this->getRequest();

		$postData = $request->getPost()->getArrayCopy();

		$orderProvider = $this->getOrderProvider();

		$paymentProvider = $this->getPaymentProvider();

		$order = $this->getOrder();
		/* @var $order Order\RecurringOrder */

		// Check if arrived here from shop or from data form.
		if ( ! $request->getQuery()->has(self::REQUEST_KEY_RETURN_FROM_FORM)) {

			// If from shop, redirect user to data form URL.

			$formDataUrl = $paymentProvider->getDataFormUrl($order);
			$response->redirect($formDataUrl);
		} else {

			// If arived here from POST with CC data - validate data and 
			// begin payment process.

			if ($this->validateRecurringOrderFormData($postData)) {
				$this->processRecurringOrderFormData($postData);
			} else {

				$formDataUrl = $paymentProvider->getDataFormUrl($order);
				$response->redirect($formDataUrl);
			}
		}

		$orderProvider->store($order);
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

			$recurringPayment = $order->getRecurringPayment();

			$recurringPayment->setStatus(RecurrintPaymentStatus::FAILED);
		}

		$orderProvider->store($order);

		$this->returnToPaymentInitiator($order->getInitiatorUrl());
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
