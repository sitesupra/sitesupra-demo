<?php

namespace Supra\Payment\Provider;

use Supra\Payment\Entity\Transaction\Transaction;
use Supra\Payment\Entity\Order\Order;
use Supra\Locale\Locale;
use Doctrine\ORM\EntityManager;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Payment\Transaction\TransactionType;
use Supra\Payment\Transaction\TransactionStatus;
use Supra\Response\ResponseInterface;
use Supra\Response\TwigResponse;

abstract class PaymentProviderAbstraction
{
  const EVENT_PROXY = 'proxy';
  const EVENT_CUSTOMER_RETURN = 'customerReturnAction';
  const EVENT_PROVIDER_NOTIFICATION = 'providerNotificationAction';

	const PROXY_URL_POSTFIX = 'proxy';
	const PROVIDER_NOTIFICATION_URL_POSTFIX = 'notification';
	const CUSTOMER_RETURN_URL_POSTFIX = 'return';
	
	const ORDER_ID = 'o';
	
	const PHASE_NAME_PROXY = 'proxy';

	/**
	 * @var string
	 */
	protected $id;

	/**
	 * @var string
	 */
	protected $proxyActionClass;

	/**
	 * @var string
	 */
	protected $providerNotificationActionClass;

	/**
	 * @var string
	 */
	protected $customerReturnActionClass;

	/**
	 * @var string;
	 */
	protected $baseUrl;

	/**
	 * @var EntityManager
	 */
	protected $em;

	/**
	 * @param EntityManager|null $em 
	 */
	function __construct(EntityManager $em = null)
	{
		if (empty($em)) {
			$em = ObjectRepository::getEntityManager($this);
		}

		$this->em = $em;
	}

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
		return $this->em;
	}

	/**
	 * @return Transaction 
	 */
	public function createPurchaseTransaction()
	{
		$transaction = new Transaction();
		$transaction->setPaymentProviderId($this->id);
		$transaction->setStatus(TransactionStatus::INITIALIZED);
		$transaction->setType(TransactionType::PURCHASE);
		$this->em->persist($transaction);

		return $transaction;
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
	 * @return string
	 */
	public function getProxyActionUrl(Order $order)
	{
		$query = http_build_query(array(self::ORDER_ID => $order->getId()));

		return $this->getBaseUrl() . '/' . self::PROXY_URL_POSTFIX . '?' . $query;
	}

	/**
	 * @return string
	 */
	public function getCustomerReturnActionUrl($queryData)
	{
		$query = http_build_query($queryData);

		return $this->getBaseUrl() . '/' . self::CUSTOMER_RETURN_URL_POSTFIX . '?' . $query;
	}

	/**
	 * @return string
	 */
	public function getProviderNotificationActionUrl($queryData)
	{
		$query = http_build_query($queryData);

		return $this->getBaseUrl() . '/' . self::PROVIDER_NOTIFICATION_URL_POSTFIX . '?' . $query;
	}

	/**
	 * @return string
	 */
	public static function CN()
	{
		return get_called_class();
	}

	/**
	 * @param Order $order 
	 */
	public function validateOrder(Order $order)
	{
		return true;
	}

	/**
	 * @param Order $order
	 * @return float
	 */
	protected function finalizeOrder(Order $order)
	{
		
	}

	/**
	 * @param Order $order 
	 */
	public function updateOrder(Order $order)
	{
		
	}

	/**
	 * @param Order $order 
	 */
	public function prepareTransaction(Order $order)
	{
		$transaction = $this->createPurchaseTransaction();
		$order->setTransaction($transaction);
		$transaction->setUserId($order->getUserId());
		$transaction->setCurrencyId($order->getCurrency()->getId());
		$transaction->setType(TransactionType::PURCHASE);
		$transaction->setStatus(TransactionStatus::IN_PROGRESS);

		$this->finalizeOrder($order);

		$transaction->setAmount($order->getTotal());

		$this->em->flush();
	}

	public function redirectToProxy(Order $order, ResponseInterface $response)
	{
		if ($response instanceof TwigResponse) {

			$proxyUrl = $this->getProxyActionUrl($order);

			$response->redirect($proxyUrl);

			$response->flush();
		}
		else {
			throw new Exception\RuntimeException('Do not know how to do redirect with this type of response.');
		}
	}

	abstract function getOrderItemDescription(Order $order, Locale $locale);
}

