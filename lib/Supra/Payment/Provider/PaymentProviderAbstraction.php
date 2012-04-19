<?php

namespace Supra\Payment\Provider;

use Supra\Payment\Entity\Transaction\Transaction;
use Supra\Payment\Order\OrderProvider;
use Supra\Payment\Entity\Order\Order;
use Supra\Payment\Order\OrderStatus;
use Supra\Payment\Entity\Order\ShopOrder;
use Supra\Payment\Entity\Order\RecurringOrder;
use Supra\Locale\Locale;
use Doctrine\ORM\EntityManager;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Payment\Transaction\TransactionType;
use Supra\Payment\Transaction\TransactionStatus;
use Supra\Response\ResponseInterface;
use Supra\Response\TwigResponse;
use Supra\Payment\Entity\RecurringPayment\RecurringPayment;
use Supra\Payment\RecurringPayment\RecurringPaymentStatus;
use Supra\Payment\PaymentEntityProvider;

abstract class PaymentProviderAbstraction
{

	const EVENT_PROXY = 'proxy';
	const EVENT_CUSTOMER_RETURN = 'customerReturnAction';
	const EVENT_PROVIDER_NOTIFICATION = 'providerNotificationAction';
	const PROXY_URL_POSTFIX = 'proxy';
	const PROVIDER_NOTIFICATION_URL_POSTFIX = 'notification';
	const CUSTOMER_RETURN_URL_POSTFIX = 'return';
	const REQUEST_KEY_SHOP_ORDER_ID = 'shopo';
	const REQUEST_KEY_ORDER_ID = 'o';
	const REQUEST_KEY_RECURRING_ORDER_ID = 'recurro';

	/**
	 * @var string
	 */
	protected $id;

	/**
	 * @var string;
	 */
	protected $baseUrl;

	/**
	 * @var EntityManager
	 */
	protected $em;

	/**
	 * @return string
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @param string $id 
	 */
	public function setId($id)
	{
		$this->id = $id;
	}

	/**
	 * @return EntityManager
	 */
	public function getEntityManager()
	{
		if (empty($this->em)) {
			$this->em = ObjectRepository::getEntityManager($this);
		}

		return $this->em;
	}

	/**
	 * @return string
	 */
	public function getBaseUrl()
	{
		return $this->baseUrl;
	}

	/**
	 * @param string $baseUrl 
	 */
	public function setBaseUrl($baseUrl)
	{
		$this->baseUrl = $baseUrl;
	}

	/**
	 * @param array $queryData
	 * @return string
	 */
	public function getProxyActionUrl($queryData)
	{
		$queryString = http_build_query($queryData);

		return $this->getBaseUrl() . '/' . self::PROXY_URL_POSTFIX . '?' . $queryString;
	}

	/**
	 * @return string
	 */
	public function getCustomerReturnActionUrl($queryData)
	{
		$queryString = http_build_query($queryData);

		return $this->getBaseUrl() . '/' . self::CUSTOMER_RETURN_URL_POSTFIX . '?' . $queryString;
	}

	/**
	 * @return string
	 */
	public function getProviderNotificationActionUrl($queryData)
	{
		$queryString = http_build_query($queryData);

		return $this->getBaseUrl() . '/' . self::PROVIDER_NOTIFICATION_URL_POSTFIX . '?' . $queryString;
	}

	/**
	 * @return Transaction 
	 */
	protected function createShopOrderTransaction(ShopOrder $order)
	{
		$transaction = new Transaction();

		$transaction->setPaymentProviderId($this->getId());

		$transaction->setUserId($order->getUserId());
		$transaction->setCurrencyId($order->getCurrency()->getId());
		$transaction->setStatus(TransactionStatus::STARTED);

		$transaction->setAmount($order->getTotal());

		return $transaction;
	}

	/**
	 * @param RecurringOrder $order
	 * @return RecurringPayment 
	 */
	protected function createRecurringOrderRecurringPayment(RecurringOrder $order)
	{
		$recurringPayment = new RecurringPayment();

		$recurringPayment->setPaymentProviderId($this->getId());
		$recurringPayment->setAmount($order->getTotal());
		$recurringPayment->setUserId($order->getUserId());
		$recurringPayment->setCurrencyId($order->getCurrency()->getId());

		$recurringPayment->setStatus(RecurringPaymentStatus::REQUESTED);

		return $recurringPayment;
	}

	/**
	 * @param ShopOrder $order 
	 */
	public function validateShopOrder(ShopOrder $order)
	{
		return true;
	}

	/**
	 * @param RecurringOrder $order 
	 */
	public function validateRecurringOrder(RecurringOrder $order)
	{
		return true;
	}

	/**
	 * @param Order $order
	 * @return float
	 */
	protected function finalizeShopOrder(ShopOrder $order)
	{
		$order->setStatus(OrderStatus::FINALIZED);
	}

	protected function finalizeRecurringOrder(RecurringOrder $order)
	{
		$order->setStatus(OrderStatus::FINALIZED);
	}

	/**
	 * @param Order $order 
	 */
	public function updateShopOrder(ShopOrder $order)
	{
		
	}

	/**
	 * @param array $queryData
	 * @param ResponseInterface $response
	 */
	protected function redirectToProxy($queryData, ResponseInterface $response)
	{
		$proxyUrl = $this->getProxyActionUrl($queryData);

		if ($response instanceof TwigResponse) {
			$response->redirect($proxyUrl);
			$response->flush();
		} else {
			throw new Exception\RuntimeException('Do not know how to do redirect with response type "' . get_class($response) . '".');
		}
	}

	/**
	 * @param ShopOrder $order
	 * @param ResponseInterface $response 
	 */
	public function processShopOrder(ShopOrder $order, ResponseInterface $response)
	{
		$transaction = $this->createShopOrderTransaction($order);

		$em = $this->getEntityManager();

		$em->persist($transaction);
		$em->flush();

		$order->setTransaction($transaction);

		$this->finalizeShopOrder($order);
	}

	/**
	 * @param RecurringOrder $order
	 * @param ResponseInterface $response 
	 */
	public function processRecurringOrder(RecurringOrder $order, ResponseInterface $response)
	{
		$recurringPayment = $this->createRecurringOrderRecurringPayment($order);
		$order->setRecurringPayment($recurringPayment);

		$this->finalizeRecurringOrder($order);
	}

	/**
	 * @param RecurringOrder $order
	 * @param float $newAmount
	 * @param string $newDescription
	 * @throws Exception\RuntimeException 
	 */
	public function initializeRecurringTransaction(RecurringOrder $order, $newAmount = null, $newDescription = null)
	{
		throw new Exception\RuntimeException('Recurring payment transactions not implemented.');
	}

	abstract function getOrderItemDescription(Order $order, Locale $locale);

	/**
	 * @return string
	 */
	public static function CN()
	{
		return get_called_class();
	}
	
	/**
	 * @return PaymentEntityProvider
	 */
	public function getPaymentEntityProvider()
	{
		if (empty($this->paymentEntityProvider)) {

			$em = $this->getEntityManager();

			$provider = new PaymentEntityProvider();
			$provider->setEntityManager($em);

			$this->paymentEntityProvider = $provider;
		}


		return $this->paymentEntityProvider;
	}

	/**
	 * @param PaymentEntityProvide $paymentEntityProvider 
	 */
	public function setPaymentEntityProvider(PaymentEntityProvider $paymentEntityProvider)
	{
		$this->paymentEntityProvider = $paymentEntityProvider;
	}

	/**
	 * @return OrderProvider
	 */
	public function getOrderProvider()
	{
		if (empty($this->orderProvider)) {

			$em = $this->getEntityManager();

			$provider = new OrderProvider();
			$provider->setEntityManager($em);

			$this->orderProvider = $provider;
		}

		return $this->orderProvider;
	}

	/**
	 * @param OrderProvier $orderProvider 
	 */
	public function setOrderProvider(PaymentEntityProvide $orderProvider)
	{
		$this->orderProvider = $orderProvider;
	}	

}
