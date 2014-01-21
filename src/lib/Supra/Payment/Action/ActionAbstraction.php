<?php

namespace Supra\Payment\Action;

use Supra\Controller\ControllerAbstraction;
use Supra\Payment\Abstraction\PaymentEntityProviderAbstraction;
use Supra\Payment\Provider\PaymentProviderAbstraction;
use Supra\Payment\Entity\Order\Order;
use Supra\Payment\Transaction\TransactionProvider;
use Supra\Payment\Entity\Order\ShopOrder;
use Supra\Payment\Entity\Order\RecurringOrder;
use Supra\Payment\RecurringPayment\RecurringPaymentProvider;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Payment\Order\OrderProvider;
use Supra\Payment\PaymentEntityProvider;
use Supra\Payment\PaymentProviderUriRouter;
use Supra\Request\HttpRequest;
use Supra\Response\HttpResponse;

abstract class ActionAbstraction extends ControllerAbstraction
{

	/**
	 * @var EntityManager
	 */
	protected $em;

	/**
	 * @var EntityRepository
	 */
	protected $orderProvider;

	/**
	 * @var PaymentProviderAbstraction
	 */
	protected $paymentProvider;

	/**
	 * @var TransactionProvider 
	 */
	protected $transactionProvider;

	/**
	 * @var TransactionProvider 
	 */
	protected $recurringPaymentProvider;

	/**
	 * @var PaymentEntityProvider
	 */
	protected $paymentEntityProvider;

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
	 * @param EntityManager $em 
	 */
	public function setEntityManager(EntityManager $em)
	{
		$this->em = $em;
	}

	/**
	 * @return TransactionProvider
	 */
	public function getTransactionProvider()
	{
		if (empty($this->transactionProvider)) {

			$em = $this->getEntityManager();

			$provider = new TransactionProvider();
			$provider->setEntityManager($em);

			$this->transactionProvider = $provider;
		}

		return $this->transactionProvider;
	}

	/**
	 * @param EntityRepository $transactionProvider 
	 */
	public function setTransactionProvider($transactionProvider)
	{
		$this->transactionProvider = $transactionProvider;
	}

	/**
	 * @return RecurringPaymentProvider
	 */
	public function getRecurringPaymentProvider()
	{
		if (empty($this->recurringPaymentProvider)) {

			$em = $this->getEntityManager();

			$provider = new RecurringPaymentProvider();
			$provider->setEntityManager($em);

			$this->recurringPaymentProvider = $provider;
		}

		return $this->recurringPaymentProvider;
	}

	public function setRecurringPaymentProvider(RecurringPaymentProvider $recurringPaymentProvider)
	{
		$this->recurringPaymentProvider = $recurringPaymentProvider;
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
	 * @param OrderProvider $orderProvider 
	 */
	public function setOrderProvider(OrderProvider $orderProvider)
	{
		$this->orderProvider = $orderProvider;
	}

	/**
	 * @return PaymentEntityProviderAbstraction
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
	 * @param PaymentEntityProvider $paymentEntityProvider 
	 */
	public function setPaymentEntiyProvider(PaymentEntityProvider $paymentEntityProvider)
	{
		$this->paymentEntityProvider = $paymentEntityProvider;
	}

	public function assertPostRequest()
	{
		if ( ! $this->getRequest()->isPost()) {
			throw new Exception\BadRequestException('POST request method is required for the action.');
		}

		$this->requestMethod = HttpRequest::METHOD_POST;
	}

	public function assertGetRequest()
	{
		if ( ! $this->getRequest()->isGet()) {
			throw new Exception\BadRequestException('GET request method is required for the action.');
		}

		$this->requestMethod = HttpRequest::METHOD_GET;
	}

	/**
	 * @param string $phaseName
	 * @param array $data 
	 */
	protected function storeDataToPaymentEntityParameters($phaseName, $data)
	{
		$orderProvider = $this->getOrderProvider();

		$order = $this->getOrder();

		$order->addToPaymentEntityParameters($phaseName, $data);

		$orderProvider->store($order);
	}

	/**
	 * @return PaymentProviderAbstraction
	 */
	protected function getPaymentProvider()
	{
		if (empty($this->paymentProvider)) {

			$request = $this->getRequest();

			$router = $request->getLastRouter();

			if ($router instanceof PaymentProviderUriRouter) {

				$paymentProvider = $router->getPaymentProvider();
				$this->setPaymentProvider($paymentProvider);
			}
		}

		return $this->paymentProvider;
	}

	/**
	 * @param PaymentProviderAbstraction $paymentProvider 
	 */
	protected function setPaymentProvider(PaymentProviderAbstraction $paymentProvider)
	{
		$this->paymentProvider = $paymentProvider;
	}

	/**
	 * @param string $initiatorUrl
	 * @param array $queryData 
	 */
	protected function returnToPaymentInitiator($initiatorUrl, $queryData = array())
	{
		$response = $this->getResponse();

		if ($response instanceof HttpResponse) {

			if ( ! $response->isRedirect()) {

				$url = http_build_url($initiatorUrl, array('query' => http_build_query($queryData)), HTTP_URL_JOIN_QUERY);

				$response->redirect($url);
			}
		}
	}
	
}

