<?php

namespace Supra\Payment\Action;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Html\HtmlTag;
use Supra\Response\TwigResponse;
use Supra\Payment\Order\OrderProvider;
use Supra\Payment\Entity\Order\Order;
use Supra\Payment\Entity\Order\ShopOrder;
use Supra\Payment\Entity\Order\RecurringOrder;
use Supra\Payment\Entity\TransactionParameter;
use Supra\Payment\Provider\PaymentProviderAbstraction;
use Supra\Payment\Provider\Event\ProxyEventArgs;
use Supra\Payment\Order\OrderStatus;
use Supra\Payment\Transaction\TransactionStatus;
use Supra\Payment\Transaction\TransactionProvider;

abstract class ProxyActionAbstraction extends ActionAbstraction
{
	const PHASE_NAME_PROXY_FORM = 'proxy-form';
	const PHASE_NAME_PROXY_REDIRECT = 'proxy-redirect';

	/**
	 * @var boolean
	 */
	protected $formAutosubmit;

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

	/**
	 * @return array
	 */
	abstract protected function getRedirectUrl();

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
			} else {
				$input->setAttribute('type', 'text');
			}

			$formElements[] = $input;
		}

		return $formElements;
	}

	/**
	 * Creates form to be submitted to payment provider.
	 */
	protected function submitFormToPaymentProvider()
	{
		$this->fireProxyEvent();

		$response = new TwigResponse($this);

		$formElements = $this->getPaymentProviderFormElements();

		$response->assign('formElements', $formElements);

		$redirectUrl = $this->getRedirectUrl();

		$response->assign('action', $redirectUrl);
		$response->assign('method', $this->formMethod);

		$response->assign('autosubmit', $this->formAutosubmit);

		$response->outputTemplate('proxyform.html.twig');

		$response->getOutputString();

		$this->response = $response;
	}

	/**
	 * Sends HTTP redirect header to client.
	 */
	protected function redirectToPaymentProvider()
	{
		$redirectUrl = $this->getRedirectUrl();

		$this->fireProxyEvent();

		$this->response->header('Location', $redirectUrl);
		$this->response->flush();
	}

	abstract protected function getProxtyEventArgs();
	
	private function fireProxyEvent()
	{
		$eventManager = ObjectRepository::getEventManager($this);

		$eventArgs = $this->getProxtyEventArgs();

		$eventManager->fire(PaymentProviderAbstraction::EVENT_PROXY, $eventArgs);
	}

	/**
	 * @param string $parameterKeyName
	 * @return Order
	 */
	protected function fetchOrderFromRequest($parameterKeyName)
	{
		$request = $this->getRequest();

		$orderId = $request->getParameter($parameterKeyName);

		$order = $this->fetchOrder($orderId);

		return $order;
	}

	/**
	 * @param string $orderId
	 * @return Order
	 */
	protected function fetchOrder($orderId)
	{
		$order = $this->getOrderProvider()
				->getOrder($orderId);

		if ($order->getStatus() != OrderStatus::FINALIZED) {
			throw new Exception\RuntimeException('Order "' . $orderId . '" is not FINALIZED!');
		}

		return $order;
	}

}
