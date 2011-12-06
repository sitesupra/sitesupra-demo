<?php

namespace Project\Payment\Paypal\Action;

use Project\Payment\Paypal;
use Supra\Payment\Action\CustomerReturnActionAbstraction;
use Supra\Payment\Transaction\TransactionProvider;
use Supra\Payment\Entity\TransactionParameter;
use Supra\Payment\Order\OrderStatus;
use Supra\Payment\Order\OrderProvider;
use Supra\Payment\Transaction\TransactionStatus;
use Supra\ObjectRepository\ObjectRepository;

class CustomerReturnAction extends CustomerReturnActionAbstraction
{
	const REQUEST_KEY_TOKEN = 'token';
	const REQUEST_KEY_PAYER_ID = 'PayerId';
	
	/**
	 *
	 * @var Paypal\PaymentProvider
	 */
	protected $paymentProvider;

	/**
	 * @var string
	 */
	protected $token;

	public function getTransactionFromRequest()
	{
		$request = $this->getRequest();

		$this->token = $request->getParameter(self::REQUEST_KEY_TOKEN);

		$searchTransactionParameter = new TransactionParameter(
						Paypal\PaymentProvider::PHASE_NAME_PROXY,
						Paypal\PaymentProvider::TRANSACTION_PARAMETER_NAME_TOKEN,
						$this->token);

		$foundTransactions = $this->transactionProvider->findTransactionsByParameter($searchTransactionParameter);

		if (empty($foundTransactions)) {
			throw new Paypal\Exception\RuntimeException('Transaction not found for token "' . $this->token . '"');
		}

		$transaction = null;
		list($transaction) = $foundTransactions;

		return $transaction;
	}

	public function processCustomerReturn()
	{
		$request = $this->getRequest();
		
		$returnAction = null;
		list(, $returnAction) = $request->getActions(2);

		switch ($returnAction) {

			case Paypal\PaymentProvider::CUSTOMER_RETURN_ACTION_RETURN: {

					$this->handleReturn();
				} break;

			case Paypal\PaymentProvider::CUSTOMER_RETURN_ACTION_CANCEL: {

					$this->handleCancel();
				} break;

			default: {
					throw new Paypal\Exception\RuntimeException('Do no known how to process return action "' . $returnAction . '".');
				}
		}
	}

	/**
	 * @param Order $order
	 * @param array $checkoutDetails 
	 */
	private function validateCheckoutDetails($checkoutDetails)
	{
		$transaction = $this->order->getTransaction();

		if ($checkoutDetails['PAYMENTREQUEST_0_CURRENCYCODE'] != $this->order->getCurrency()->getIsoCode()) {

			throw new Exception\RuntimeException('Currency codes do not match for order and checkout details for transaction "' . $transaction->getId() . '".');
		}

		if ($checkoutDetails['PAYMENTREQUEST_0_AMT'] != $this->order->getTotal()) {

			throw new Exception\RuntimeException('Money amounts do not match for order and checkout details for transaction "' . $transaction->getId() . '".');
		}
	}

	/**
	 * Handles case when user returns with supposedly confirmed payment.
	 * @return void
	 */
	public function handleReturn()
	{
		$transaction = $this->order->getTransaction();

		// Fetch checkout details from Paypal.
		$checkoutDetails = $this->paymentProvider->makeGetExpressCheckoutDetailsCall($this->token);
		\Log::debug('CHECKOUT DETAILS: ', $checkoutDetails);

		// Store checkout details to transaction parameters.
		$this->storeDataToTransactionParamaters(Paypal\PaymentProvider::PHASE_NAME_CHECKOUT_DETAILS, $checkoutDetails);

		$this->validateCheckoutDetails($checkoutDetails);

		// Fire event on received checkout details.
		$eventManager = ObjectRepository::getEventManager($this);

		$eventArgs = new Paypal\Event\PayerCheckoutDetailsEventArgs();
		$eventArgs->setOrder($this->order);
		$eventArgs->setPayerCheckoutDetails($checkoutDetails);

		$eventManager->fire(Paypal\PaymentProvider::EVENT_PAYER_CHECKOUT_DETAILS, $eventArgs);
		// ---
		// Approve payment from payer.
		$doPaymentResult = $this->paymentProvider->makeDoExpressCheckoutPaymentCall($this->order, $checkoutDetails);
		\Log::debug('DO PAYMENT: ', $doPaymentResult);

		// Store results in transaction parameters.
		$this->storeDataToTransactionParamaters(Paypal\PaymentProvider::PHASE_NAME_DO_PAYMENT, $doPaymentResult);

		// Check if payment has been successfull.
		switch ($doPaymentResult['PAYMENTINFO_0_ACK']) {

			case 'Success': {
					
				} break;

			default: {

					$transaction->setStatus(TransactionStatus::FAILED);
					$this->order->setStatus(OrderStatus::PAYMENT_FAILED);

					$this->transactionProvider->store($transaction);
					$this->orderProvider->store($this->order);

					return;
				}
		}

		switch ($doPaymentResult['PAYMENTINFO_0_PAYMENTSTATUS']) {

			case 'Pending': {

					$transaction->setStatus(TransactionStatus::IN_PROGRESS2);

					$this->order->setStatus(OrderStatus::PAYMENT_PENDING);
				} break;

			case 'Completed': {

					$transaction->setStatus(TransactionStatus::SUCCESS);

					$this->order->setStatus(OrderStatus::PAYMENT_RECEIVED);
				} break;

			default: {

					$transaction->setStatus(TransactionStatus::FAILED);

					$this->order->setStatus(OrderStatus::PAYMENT_FAILED);
				}
		}

		$this->transactionProvider->store($transaction);
		$this->orderProvider->store($this->order);
	}

	/**
	 * Handles case when customer returns using "Cancel and return".
	 */
	public function handleCancel()
	{
		$transaction = $this->order->getTransaction();

		$transaction->setStatus(TransactionStatus::FAILED);
		$this->transactionProvider->store($transaction);

		$this->order->setStatus(OrderStatus::PAYMENT_CANCELED);
		$this->orderProvider->store($this->order);
	}

}
