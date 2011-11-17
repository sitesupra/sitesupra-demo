<?php

namespace Supra\Payment\Provider;

use Supra\Controller\ControllerAbstraction;
use Supra\Controller\FrontController;
use Supra\Payment\Provider\PaymentProviderAbstraction;

abstract class RequestControllerAbstraction extends ControllerAbstraction
{

	protected $proxyActionClass;
	protected $providerNotificationActionClass;
	protected $customerReturnActionClass;
	
	/**
	 * @var PaymentProviderAbstraction
	 */
	protected $paymentProvider;

	/**
	 * @param string $proxyActionClass
	 * @param string $providerNotificationActionClass
	 * @param string $customerReturnActionClass 
	 */
	function __construct($proxyActionClass, $providerNotificationActionClass, $customerReturnActionClass, $paymentProviderClass)
	{
		$this->proxyActionClass = $proxyActionClass;
		$this->providerNotificationActionClass = $providerNotificationActionClass;
		$this->customerReturnActionClass = $customerReturnActionClass;
		
		$this->paymentProvider = new $paymentProviderClass();
	}

	private function executeAction($actionControllerClass)
	{
		$actionController = FrontController::getInstance()->runController($actionControllerClass, $this->getRequest());
		$actionController->getResponse()->flushToResponse($this->getResponse());
	}

	public function executeProxyAction()
	{
		$this->executeAction($this->proxyActionClass);
	}

	public function executeProviderNotificationAction()
	{
		$this->executeAction($this->providerNotificationActionClass);
	}

	public function executeCustomerReturnAction()
	{
		$this->executeAction($this->customerReturnActionClass);
	}

}

