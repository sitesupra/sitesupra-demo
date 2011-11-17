<?php

namespace Supra\Payment\Action;

use Supra\Payment\Entity\Order;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Html\HtmlTag;
use Supra\Response\TwigResponse;

abstract class ProxyActionAbstraction extends ActionAbstraction
{

	/**
	 * @var Order
	 */
	protected $order;

	/**
	 * @var boolean
	 */
	protected $autosubmit;

	function __construct()
	{
		$this->fetchOrderFromRequest();
	}

	public function setAutosubmit($autosubmit)
	{
		$this->autosubmit = $autosubmit;
	}

	/**
	 * @return array
	 */
	abstract function getFormData();

	/**
	 * @return array
	 */
	protected function getFormElements()
	{
		$formData = $this->getFormData();

		$formElements = array();

		foreach ($formData as $name => $value) {

			$hiddenInput = new HtmlTag('input');
			$hiddenInput->setAttribute('hidden', true);
			$hiddenInput->setAttribute('name', $name);
			$hiddenInput->setAttribute('value', $value);

			$formElements[] = $hiddenInput;
		}

		return $formElements;
	}

	public function execute()
	{
		$response = new TwigResponse($this);

		$formElements = $this->getFormElements();
		
		$providerActionUrl = $this->getProviderActionUrl();

		$response->assign('formElements', $formElements);
		$response->assign('providerActionUrl', $providerActionUrl);
		$response->assign('autosubmit', $this->autosubmit);

		$response->outputTemplate('proxyform.html.twig');

		$response->getOutputString();

		$this->response = $response;
	}

	/**
	 * Fetches order from entity manager.
	 * @return Order
	 */
	public function fetchOrderFromRequest()
	{
		$orderId = $this->getRequest()->getParameter('orderId');

		if (empty($orderId)) {
			throw new Exception\PaymentActionRuntimeException('No order id');
		}

		$em = ObjectRepository::getEntityManager($this);

		$orderRepository = $em->getRepository(Order::CN());

		$this->order = $orderRepository->find($orderId);

		if (empty($this->order)) {
			throw new Exception\PaymentActionRuntimeException('Order not found for id "' . $orderId . '"');
		}

		$orderTransaction = $this->order->getTransaction();

		if (empty($orderTransaction)) {
			throw new Exception\PaymentActionRuntimeException('Order "' . $orderId . '" does not have a transaction.');
		}

		$paymentProviderId = $orderTransaction->getPaymentProviderId();

		$paymentProviderCollection = ObjectRepository::getPaymentProviderCollection($this);

		$this->paymentProvider = $paymentProviderCollection->get($paymentProviderId);
	}

}
