<?php

namespace Supra\Payment;

use Supra\Controller\ControllerAbstraction;
use Supra\Controller\FrontController;
use Supra\ObjectRepository\ObjectRepository;

abstract class RequestControllerAbstraction extends ControllerAbstraction
{

	protected $proxyActionClass;
	protected $providerNotificationActionClass;
	protected $customerReturnActionClass;

	/**
	 * @param string $proxyActionClass
	 * @param string $providerNotificationActionClass
	 * @param string $customerReturnActionClass 
	 */
	function __construct($proxyActionClass, $providerNotificationActionClass, $customerReturnActionClass)
	{
		$this->proxyActionClass = $proxyActionClass;
		$this->providerNotificationActionClass = $providerNotificationActionClass;
		$this->customerReturnActionClass = $customerReturnActionClass;
	}

	/**
	 * @param string $actionControllerClass 
	 */
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

