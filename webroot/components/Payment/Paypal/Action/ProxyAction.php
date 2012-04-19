<?php

namespace Project\Payment\Paypal\Action;

use Supra\Payment\Action\CommonProxyActionAbstraction;
use Project\Payment\Paypal;
use Supra\Payment\Order\OrderProvider;
use Supra\Payment\Order\OrderStatus;
use Supra\Payment\Entity\Order\Order;
use Supra\Payment\Entity\Order\ShopOrder;
use Supra\Payment\Order\RecurringOrderStatus;
use Supra\Payment\Entity\Order\RecurringOrder;
use Supra\Payment\Transaction\TransactionProvider;
use Supra\Payment\Transaction\TransactionStatus;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Payment\Provider\Event\ProxyEventArgs;
use Supra\Payment\RecurringPayment\RecurringPaymentStatus;

class ProxyAction extends CommonProxyActionAbstraction
{
	const PHASE_NAME_PAYPAL_SET_EXPRESS_CHECKOUT = 'paypal-setExpressCheckout';

	/**
	 * @var Order
	 */
	protected $order;

	public function execute()
	{
		$request = $this->getRequest();

		$shopOrderId = $request->getParameter(Paypal\PaymentProvider::REQUEST_KEY_SHOP_ORDER_ID, null);

		$recurringOrderId = $request->getParameter(Paypal\PaymentProvider::REQUEST_KEY_RECURRING_ORDER_ID, null);

		if ( ! empty($shopOrderId)) {

			$order = $this->fetchOrder($shopOrderId);
			$this->setOrder($order);

			$this->executeShopOrderProxyAction();
		} else {
			if ( ! empty($recurringOrderId)) {

				$order = $this->fetchOrder($recurringOrderId);
				$this->setOrder($order);

				$this->executeRecurringOrderProxyAction();
			} else {
				throw new Exception\RuntimeException('Could not determine order type.');
			}
		}
	}

	/**
	 * @return Order
	 */
	public function getOrder()
	{
		if (empty($this->order)) {
			throw new Exception\RuntimeException('Order not set.');
		}

		return $this->order;
	}

	/**
	 * @param Order $order 
	 */
	public function setOrder(Order $order)
	{
		$this->order = $order;
	}

	public function executeShopOrderProxyAction()
	{
		$orderProvider = $this->getOrderProvider();
		$paymentProvider = $this->getPaymentProvider();

		/* @var $order ShopOrder */
		$order = $this->getOrder();

		$setExpressCheckoutResult = $paymentProvider->makeSetExpressCheckoutCall($order);

		$order->addToPaymentEntityParameters(Paypal\PaymentProvider::PHASE_NAME_PAYPAL_SET_EXPRESS_CHECKOUT, $setExpressCheckoutResult);

		\Log::debug('Paypal setExpressCheckout API call result: ', $setExpressCheckoutResult);

		if (empty($setExpressCheckoutResult[Paypal\PaymentProvider::REQUEST_KEY_TOKEN])) {

			$transaction = $order->getTransaction();

			$transaction->setStatus(TransactionStatus::PROVIDER_ERROR);
			$order->setStatus(OrderStatus::PAYMENT_START_ERROR);

			$orderProvider->store($order);

			throw new Paypal\Exception\RuntimeException('Did not get TOKEN from Paypal\'s SetExpressCheckout API call.');
		}

		$order->setStatus(OrderStatus::PAYMENT_STARTED);

		$this->proxyData = $setExpressCheckoutResult;

		$this->redirectToPaymentProvider();

		$orderProvider->store($order);
	}

	public function executeRecurringOrderProxyAction()
	{
		$orderProvider = $this->getOrderProvider();
		$paymentProvider = $this->getPaymentProvider();

		$order = $this->getOrder();
		/* @var $order RecurringOrder */

		$setExpressCheckoutResult = $paymentProvider->makeSetExpressCheckoutCall($order);

		$order->addToPaymentEntityParameters(Paypal\PaymentProvider::PHASE_NAME_PAYPAL_SET_EXPRESS_CHECKOUT, $setExpressCheckoutResult);
		$orderProvider->store($order);
		
		\Log::debug('Paypal setExpressCheckout API call result: ', $setExpressCheckoutResult);

		if (empty($setExpressCheckoutResult[Paypal\PaymentProvider::REQUEST_KEY_TOKEN])) {

			$recurringPayment = $order->getRecurringPayment();

			$recurringPayment->setStatus(RecurringPaymentStatus::PROVIDER_ERROR);

			$orderProvider->store($order);

			throw new Paypal\Exception\RuntimeException('Did not get TOKEN from Paypal\'s SetExpressCheckout API call.');
		}

		$order->setStatus(OrderStatus::PAYMENT_STARTED);

		$this->proxyData = $setExpressCheckoutResult;

		$this->redirectToPaymentProvider();

		$orderProvider->store($order);
	}

	/**
	 * @return array
	 */
	protected function getRedirectUrl()
	{
		$paymentProvider = $this->getPaymentProvider();

		$queryData = array(
			'cmd' => '_express-checkout',
			'token' => $this->proxyData[Paypal\PaymentProvider::REQUEST_KEY_TOKEN]
		);

		$url = $paymentProvider->getPaypalRedirectUrl($queryData);

		return $url;
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

}
