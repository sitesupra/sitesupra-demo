<?php

namespace Supra\Payment\Action;

use Supra\Controller\ControllerAbstraction;
use Supra\Payment\Provider\PaymentProviderAbstraction;

abstract class ActionAbstraction extends ControllerAbstraction
{

	/**
	 * @var PaymentProviderAbstraction
	 */
	protected $paymentProvider;

	public function assertPostRequest()
	{
		if ( ! $this->getRequest()->isPost()) {
			throw new Exception\BadRequestException('POST request method is required for the action.');
		}

		$this->requestMethod = Request\HttpRequest::METHOD_POST;
	}

	public function assertGetRequest()
	{
		if ( ! $this->getRequest()->isGet()) {
			throw new Exception\BadRequestException('GET request method is required for the action.');
		}

		$this->requestMethod = Request\HttpRequest::METHOD_GET;
	}

	/**
	 * @return PaymentProviderAbstraction
	 */
	public function getPaymentProvider()
	{
		return $this->paymentProvider;
	}

}
