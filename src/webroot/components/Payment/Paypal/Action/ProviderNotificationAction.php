<?php

namespace Project\Payment\Paypal\Action;

use Supra\Request\HttpRequest;
use Supra\ObjectRepository\ObjectRepository;
use Project\Payment\Paypal;
use Supra\Payment\Provider\Event\ProviderNotificationEventArgs;
use Supra\Payment\Provider\PaymentProviderAbstraction;
use Supra\Payment\Entity\TransactionParameter;
use Supra\Payment\Order\OrderStatus;
use Supra\Payment\Transaction\TransactionStatus;
use Supra\Payment\Action\ProviderNotificationActionAbstraction;

class ProviderNotificationAction extends ProviderNotificationActionAbstraction
{
	const NOTIFICATION_KEY_TXN_ID = 'txn_id';
	const NOTIFICATION_KEY_IPN_ID = 'ipn_track_id';
	const NOTIFICATION_KEY_TXN_TYPE = 'txn_type';
	const NOTIFICATION_KEY_PAYMENT_STATUS = 'payment_status';

	/**
	 * @return Transaction
	 */
	public function getTransactionFromNotificationData()
	{
		$paypalTransactionId = $this->notificationData->get(self::NOTIFICATION_KEY_TXN_ID);

		$searchTransactionParameter = new TransactionParameter(
						Paypal\PaymentProvider::PHASE_NAME_DO_PAYMENT,
						Paypal\PaymentProvider::TRANSCACTION_PARAMETER_TRANSACTIONID,
						$paypalTransactionId);

		$foundTransactions = $this->transactionProvider->findTransactionsByParameter($searchTransactionParameter);

		if (empty($foundTransactions)) {
			throw new Paypal\Exception\RuntimeException('Transaction not found for Paypal transaction "' . $paypalTransactionId . '"');
		}

		$transaction = null;
		list($transaction) = $foundTransactions;

		return $transaction;
	}

	/**
	 * @return string
	 */
	protected function getNotificationPhaseName()
	{
		return Paypal\PaymentProvider::PHASE_NAME_IPN . $this->getRequest()->getPostValue(self::NOTIFICATION_KEY_IPN_ID);
	}

	public function processProviderNotification()
	{
		// Verify IPN data.
		if ($this->paymentProvider->validateIpn($this->notificationData->getArrayCopy()) == false) {
			throw new Exception\RuntimeException('IPN verification failed.');
		}

		$txnType = $this->notificationData->get(self::NOTIFICATION_KEY_TXN_TYPE);

		switch ($txnType) {

			case 'express_checkout': {

					$this->handleExpressCheckout();
				} break;

			default:
				throw new Exception\RuntimeException('Do not known how to handle IPN of type "' . $txnType . '".');
		}
	}

	public function handleExpressCheckout()
	{
		$paymentStatus = $this->notificationData->get(self::NOTIFICATION_KEY_PAYMENT_STATUS);

		$transaction = $this->order->getTransaction();

		switch ($paymentStatus) {

			case Paypal\PaypalPaymentStatus::COMPLETED: {

					$this->order->setStatus(OrderStatus::PAYMENT_RECEIVED);
				} break;

			case Paypal\PaypalPaymentStatus::PENDING: {

					$this->order->setStatus(OrderStatus::PAYMENT_PENDING);

					$transaction->setStatus(TransactionStatus::IN_PROGRESS2);
				} break;

			default: {

					$this->order->setStatus(OrderStatus::SYSTEM_ERROR);
				}
		}
	}

}

