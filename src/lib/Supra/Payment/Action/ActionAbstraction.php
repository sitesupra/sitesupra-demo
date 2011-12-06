<?php

namespace Supra\Payment\Action;

use Supra\Controller\ControllerAbstraction;
use Supra\Payment\Provider\PaymentProviderAbstraction;
use Supra\Payment\Entity\Order\Order;
use Supra\Payment\Transaction\TransactionProvider;

abstract class ActionAbstraction extends ControllerAbstraction
{
	/**
	 * @var Order
	 */
	protected $order;
	
	/**
	 * @var PaymentProviderAbstraction
	 */
	protected $paymentProvider;
	
	/**
	 * @var TransactionProvider 
	 */
	protected $transactionProvider;

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
	 * @param string $phaseName
	 * @param array $parameters 
	 */
	protected function storeDataToTransactionParamaters($phaseName, $parameters)
	{
		$transaction = $this->order->getTransaction();

		foreach ($parameters as $key => $value) {

			$transaction->makeAndAddPrameter(
					$phaseName, $key, $value);
		}

		$this->transactionProvider->store($transaction);
	}	

}
