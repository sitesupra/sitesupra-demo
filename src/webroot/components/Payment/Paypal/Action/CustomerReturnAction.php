<?php

namespace Project\Payment\Paypal\Action;

use Supra\ObjectRepository\ObjectRepository;
use Supra\Payment\Action\CustomerReturnActionAbstraction;
use Supra\Payment\Transaction\TransactionProvider;
use Supra\Payment\Entity\Transaction\TransactionParameter;
use Supra\Payment\Entity\Transaction\Transaction;
use Supra\Payment\Entity\RecurringPayment\RecurringPayment;
use Supra\Payment\Entity\Abstraction\PaymentEntity;
use Supra\Payment\Entity\Order\Order;
use Supra\Payment\Entity\Order\ShopOrder;
use Supra\Payment\Entity\Order\RecurringOrder;
use Supra\Payment\Order\OrderStatus;
use Supra\Payment\Order\OrderProvider;
use Supra\Payment\Transaction\TransactionStatus;
use Supra\Payment\Order\RecurringOrderStatus;
use Supra\Payment\PaymentEntityProvider;
use Project\Payment\Paypal;
use Supra\Payment\Provider\Event\CustomerReturnEventArgs;
use Supra\Payment\RecurringPayment\RecurringPaymentStatus;

class CustomerReturnAction extends CustomerReturnActionAbstraction
{
	const REQUEST_KEY_TOKEN = 'token';
	const REQUEST_KEY_PAYER_ID = 'PayerId';

	/**
	 * @var Order
	 */
	protected $order;

	/**
	 *
	 * @var string
	 */
	protected $token;

	/**
	 * @return Order
	 */
	protected function getOrder()
	{
		if (empty($this->order)) {
			throw new Paypal\Exception\RuntimeException('Order is not set.');
		}

		return $this->order;
	}

	/**
	 * @param Order $order 
	 */
	protected function setOrder(Order $order)
	{
		$this->order = $order;
	}

	public function execute()
	{
		$request = $this->getRequest();

		$returnAction = null;
		list(, $returnAction) = $request->getActions(2);

		switch ($returnAction) {

			case Paypal\PaymentProvider::CUSTOMER_RETURN_ACTION_RETURN: {

					$this->handlePaypalReturn();
				} break;

			case Paypal\PaymentProvider::CUSTOMER_RETURN_ACTION_CANCEL: {

					$this->handlePaypalCancel();
				} break;

			default: {
					throw new Paypal\Exception\RuntimeException('Do no known how to process return action "' . $returnAction . '".');
				}
		}
	}

	/**
	 * Locates and sets the $this->order coresponding to Paypal token value.
	 * @return Order
	 */
	private function fetchOrderByPaypalToken()
	{
		$paymentEntityProvider = $this->getPaymentEntityProvider();

		$phaseName = Paypal\PaymentProvider::PHASE_NAME_PAYPAL_SET_EXPRESS_CHECKOUT;
		$name = Paypal\PaymentProvider::TRANSACTION_PARAMETER_NAME_TOKEN;

		$foundPaymentEntities = $paymentEntityProvider->findByParameterPhaseAndNameAndValue($phaseName, $name, $this->token);

		if (empty($foundPaymentEntities)) {
			throw new Paypal\Exception\RuntimeException('Paypal payment entities not found for token "' . $this->token . '"');
		}

		if (count($foundPaymentEntities) > 1) {
			throw new Paypal\Exception\RuntimeException('Found more than one payment entity for Paypal token "' . $this->token . '"');
		}

		/* @var $paymentEntity PaymentEntity */
		$paymentEntity = array_pop($foundPaymentEntities);

		$orderProvider = $this->getOrderProvider();

		$order = $orderProvider->getOrderByPaymentEntity($paymentEntity);

		if (empty($order)) {
			throw new Paypal\Exception\RuntimeException('Could not get order from payment entity with id "' . $paymentEntity->getId() . '"');
		}

		$this->setOrder($order);

		return $order;
	}

	protected function handlePaypalReturn()
	{
		$request = $this->getRequest();

		$this->token = $request->getParameter(self::REQUEST_KEY_TOKEN);

		if (empty($this->token)) {
			throw new Paypal\Exception\RuntimeException('Paypal token not found in request parameters.');
		}

		$order = $this->fetchOrderByPaypalToken();

		if ($order instanceof ShopOrder) {
			$this->handleShopOrder($order);
		} else if ($order instanceof RecurringOrder) {
			$this->handleRecurringOrder($order);
		} else {
			throw new Paypal\Exception\RuntimeException('Do not know what to do with "' . get_class($order) . '" order');
		}
	}

	protected function processShopOrder(ShopOrder $order)
	{
		$paymentProvider = $this->getPaymentProvider();
		$orderProvider = $this->getOrderProvider();

		// Fetch checkout details from Paypal.
		$checkoutDetails = $paymentProvider->makeGetExpressCheckoutDetailsCall($this->token);
		\Log::debug('CHECKOUT DETAILS: ', $checkoutDetails);

		// Store checkout details to transaction parameters.
		$order->addToPaymentEntityParameters(Paypal\PaymentProvider::PHASE_NAME_CHECKOUT_DETAILS, $checkoutDetails);
		$orderProvider->store($order);

		$this->validateShopOrderCheckoutDetails($checkoutDetails);

		// Fire event on received checkout details.
		$eventManager = ObjectRepository::getEventManager($this);
		$eventArgs = new Paypal\Event\PayerCheckoutDetailsEventArgs();
		$eventArgs->setOrder($order);
		$eventArgs->setPayerCheckoutDetails($checkoutDetails);
		$eventManager->fire(Paypal\PaymentProvider::EVENT_PAYER_CHECKOUT_DETAILS, $eventArgs);

		// Approve payment from payer.
		$doPaymentResult = $paymentProvider->makeDoExpressCheckoutPaymentCall($order, $checkoutDetails);
		\Log::debug('DO PAYMENT: ', $doPaymentResult);

		// Store results in transaction parameters.
		$order->addToPaymentEntityParameters(Paypal\PaymentProvider::PHASE_NAME_DO_PAYMENT, $doPaymentResult);
		$orderProvider->store($order);

		$transaction = $order->getTransaction();

		// Check if payment has been successfull.
		switch ($doPaymentResult['PAYMENTINFO_0_ACK']) {

			case 'Success': {
					
				} break;

			default: {

					$transaction->setStatus(TransactionStatus::FAILED);

					$orderProvider->store($order);

					return;
				}
		}

		switch ($doPaymentResult['PAYMENTINFO_0_PAYMENTSTATUS']) {

			case 'Pending': {

					$transaction->setStatus(TransactionStatus::PENDING);
				} break;

			case 'Completed': {

					$transaction->setStatus(TransactionStatus::SUCCESS);
				} break;

			default: {

					$transaction->setStatus(TransactionStatus::FAILED);
				}
		}

		$orderProvider->store($order);

		// Return to shop.
		$returnQueryData = array(
			self::QUERY_KEY_SHOP_ORDER_ID => $order->getId()
		);

		$returnUrl = $order->getReturnUrl();

		$this->returnToPaymentInitiator($returnUrl, $returnQueryData);
	}

	protected function processRecurringOrder(RecurringOrder $order)
	{
		$paymentProvider = $this->getPaymentProvider();
		$orderProvider = $this->getOrderProvider();

		$recurringPayment = $order->getRecurringPayment();

		// Fetch checkout details from Paypal.
		$checkoutDetails = $paymentProvider->makeGetExpressCheckoutDetailsCall($this->token);
		\Log::debug('CHECKOUT DETAILS: ', $checkoutDetails);

		// Store checkout details to transaction parameters.
		$order->addToPaymentEntityParameters(Paypal\PaymentProvider::PHASE_NAME_CHECKOUT_DETAILS, $checkoutDetails);
		$orderProvider->store($order);

		$this->validateRecurringOrderCheckoutDetails($checkoutDetails);

		// Approve payment from payer.
		$createRecurringPaymentResult = $paymentProvider->makeCreateRecurringPaymentsProfileCall($order, $checkoutDetails);
		\Log::debug('CREATE RECURRING PAYMENT: ', $createRecurringPaymentResult);

		/**
		 * 	(
		  [PROFILEID] => I-6S3VS7S2MK84
		  [PROFILESTATUS] => ActiveProfile
		  [TIMESTAMP] => 2012-01-26T10:16:43Z
		  [CORRELATIONID] => 434b3d4f225df
		  [ACK] => Success
		  [VERSION] => 82.0
		  [BUILD] => 2488002
		  )
		 */
		if (
				$createRecurringPaymentResult['ACK'] == 'Success' &&
				$createRecurringPaymentResult['PROFILESTATUS'] == 'ActiveProfile'
		) {
			$recurringPayment->setStatus(RecurringPaymentStatus::CONFIRMED);
		} else {
			$recurringPayment->setStatus(RecurringPaymentStatus::PAYER_CANCELED);
		}

		// Store results in transaction parameters.
		$order->addToPaymentEntityParameters(Paypal\PaymentProvider::PHASE_NAME_CREATE_RECURRING_PAYMENT, $createRecurringPaymentResult);
		$orderProvider->store($order);

		// Return to shop.
		$returnQueryData = array(
			self::QUERY_KEY_RECURRING_ORDER_ID => $order->getId()
		);
		$returnUrl = $order->getReturnUrl();
		$this->returnToPaymentInitiator($returnUrl, $returnQueryData);
	}

	/**
	 * @param Order $order
	 * @param array $checkoutDetails 
	 */
	private function validateShopOrderCheckoutDetails($checkoutDetails)
	{
		/* @var $order ShopOrder */
		$order = $this->getOrder();

		$transaction = $order->getTransaction();

		if ($checkoutDetails['PAYMENTREQUEST_0_CURRENCYCODE'] != $order->getCurrency()->getIsoCode()) {

			throw new Exception\RuntimeException('Currency codes do not match for order and checkout details for transaction "' . $transaction->getId() . '".');
		}

		if ($checkoutDetails['PAYMENTREQUEST_0_AMT'] != $order->getTotal()) {

			throw new Exception\RuntimeException('Money amounts do not match for order and checkout details for transaction "' . $transaction->getId() . '".');
		}
	}

	/**
	 * @param array $checkoutDetails 
	 */
	private function validateRecurringOrderCheckoutDetails($checkoutDetails)
	{
		
	}

	/**
	 * Handles case when customer returns using "Cancel and return".
	 */
	protected function handlePaypalCancel()
	{
		$request = $this->getRequest();

		$this->token = $request->geParameter(self::REQUEST_KEY_TOKEN);

		if (empty($this->token)) {
			throw new Paypal\Exception\RuntimeException('Paypal token not found in request parameters.');
		}

		$order = $this->fetchOrderByPaypalToken();

		if ($order instanceof ShopOrder) {
			$this->handlePaypalShopOrderCancel();
		} else if ($order instanceof RecurringOrder) {
			$this->handlePaypalRecurringOrderCancel();
		} else {
			throw new Paypal\Exception\RuntimeException('Do not know how to cancel order of type "' . get_class($order) . '"');
		}
	}

	protected function handlePaypalRecurringOrderCancel()
	{
		$orderProvider = $this->getOrderProvider();

		/* @var $order RecurringOrder */
		$order = $this->getOrder();


		$order->getRecurringPayment()
				->setStatus(RecurringPaymentStatus::PAYER_CANCELED);

		$orderProvider->store($order);

		$this->fireCustomerReturnEvent();

		// Return to shop.
		$returnQueryData = array(
			self::QUERY_KEY_SHOP_ORDER_ID => $order->getId()
		);
		$returnUrl = $order->getReturnUrl();
		$this->returnToPaymentInitiator($returnUrl, $returnQueryData);
	}

	protected function handlePaypalShopOrderCancel()
	{
		$orderProvider = $this->getOrderProvider();

		/* @var $order ShopOrder */
		$order = $this->getOrder();

		$order->getTransaction()
				->setStatus(TransactionStatus::PAYER_CANCELED);

		$orderProvider->store($order);

		$this->fireCustomerReturnEvent();

		// Return to shop.
		$returnQueryData = array(
			self::QUERY_KEY_SHOP_ORDER_ID => $order->getId()
		);
		$returnUrl = $order->getReturnUrl();
		$this->returnToPaymentInitiator($returnUrl, $returnQueryData);
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

}
