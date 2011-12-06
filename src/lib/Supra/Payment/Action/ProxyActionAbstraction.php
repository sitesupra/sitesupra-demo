<?php

namespace Supra\Payment\Action;

use Doctrine\ORM\EntityManager;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Html\HtmlTag;
use Supra\Response\TwigResponse;
use Supra\Payment\Order\OrderProvider;
use Supra\Payment\Entity\Order\Order;
use Supra\Payment\Entity\TransactionParameter;
use Supra\Payment\Provider\PaymentProviderAbstraction;
use Supra\Payment\Provider\Event\ProxyEventArgs;
use Supra\Payment\Order\OrderStatus;
use Supra\Payment\Transaction\TransactionStatus;
use Supra\Payment\Transaction\TransactionProvider;

abstract class ProxyActionAbstraction extends ActionAbstraction
{
	/**
	 * @var Order
	 */
	protected $order;

	/**
	 * @var boolean
	 */
	protected $formAutosubmit;

	/**
	 * @var string
	 */
	protected $formAction;

	/**
	 * @var string
	 */
	protected $formMethod;

	/**
	 * @var array
	 */
	protected $proxyData;

	/**
	 * @var string
	 */
	protected $redirectUrl;

	abstract protected function preparePayment();

	abstract protected function beginPaymentProcedure();

	public function execute()
	{
		$this->fetchOrderFromRequest();

		$this->fetchPaymentProvider();

		$this->preparePayment();

		$this->beginPaymentProcedure();
	}

	/**
	 * @return array
	 */
	protected function getPaymentProviderFormElements()
	{
		$formElements = array();

		foreach ($this->proxyData as $name => $value) {

			$input = new HtmlTag('input');

			$input->setAttribute('name', $name);
			$input->setAttribute('value', $value);

			if ($this->autosubmit) {
				$input->setAttribute('type', 'hidden');
			}
			else {
				$input->setAttribute('type', 'text');
			}

			$formElements[] = $input;
		}

		return $formElements;
	}

	/**
	 * Writes TransactionParameters to database.
	 */
	protected function storeProxyPhaseTransactionParameters()
	{
		$transactionProvider = new TransactionProvider();
		
		$transaction = $this->order->getTransaction();

		foreach ($this->proxyData as $name => $value) {

			$transactionParameter = new TransactionParameter();

			$transactionParameter->setPhaseName(PaymentProviderAbstraction::PHASE_NAME_PROXY);

			$transactionParameter->setName($name);
			$transactionParameter->setValue($value);

			$transactionParameter->setTransaction($transaction);

			$transaction->addParameter($transactionParameter);
		}
		
		$transactionProvider->store($transaction);
	}

	/**
	 * Creates form to be submitted to payment provider.
	 */
	protected function submitFormToPaymentProvider()
	{
		$this->storeProxyPhaseTransactionParameters();

		$response = new TwigResponse($this);

		$formElements = $this->getPaymentProviderFormElements();

		$response->assign('formElements', $formElements);

		$response->assign('action', $this->formAction);
		$response->assign('method', $this->formMethod);

		$response->assign('autosubmit', $this->formAutosubmit);

		$response->outputTemplate('proxyform.html.twig');

		$response->getOutputString();

		$this->response = $response;

		$this->order->setStatus(OrderStatus::PAYMENT_STARTED);
		$this->em->persist($this->order);
		$this->em->flush();

		$this->fireProxyActionEvent();
	}

	/**
	 * @return array
	 */
	abstract function getRedirectQueryData();

	protected function redirectToPaymentProvider()
	{
		$this->storeProxyPhaseTransactionParameters();

		$this->order->setStatus(OrderStatus::PAYMENT_STARTED);
		
		$this->order->getTransaction()->setStatus(TransactionStatus::IN_PROGRESS);

		$orderProvider = new OrderProvider();
		$orderProvider->store($this->order);

		$this->fireProxyActionEvent();

		$queryData = $this->getRedirectQueryData();

		$query = http_build_query($queryData)
		;
		$this->response->header('Location', $this->redirectUrl . '?' . $query);
		$this->response->flush();
	}

	/**
	 * Fetches order from entity manager.
	 * @return Order
	 */
	public function fetchOrderFromRequest()
	{
		$request = $this->getRequest();

		$orderId = $request->getParameter(PaymentProviderAbstraction::ORDER_ID);

		if (empty($orderId)) {
			throw new Exception\PaymentActionRuntimeException('No order id');
		}

		$orderProvider = new OrderProvider();
		$this->order = $orderProvider->getOrder($orderId);

		if ($this->order->getStatus() != OrderStatus::OPEN) {
			throw new Exception\RuntimeException('Order "' . $orderId . '" is not fresh!');
		}
	}

	public function fetchPaymentProvider()
	{
		$paymentProviderCollection = ObjectRepository::getPaymentProviderCollection($this);

		$transaction = $this->order->getTransaction();

		$paymentProviderId = $transaction->getPaymentProviderId();

		$this->paymentProvider = $paymentProviderCollection->get($paymentProviderId);
	}

	private function fireProxyActionEvent()
	{
		$eventManager = ObjectRepository::getEventManager($this);
		
		$eventArgs = new ProxyEventArgs();
		$eventArgs->setOrder($this->order);
		$eventManager->fire(PaymentProviderAbstraction::EVENT_PROXY, $eventArgs);
	}

}
