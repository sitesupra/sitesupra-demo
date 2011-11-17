<?php

namespace Project\Payment\DummyPay;

use Supra\Payment\Provider\RequestControllerAbstraction;

class RequestController extends RequestControllerAbstraction
{

	function __construct()
	{
		parent::__construct(
				Action\ProxyAction::CN(), Action\ProviderNotificationAction::CN(), Action\CustomerReturnAction::CN(), PaymentProvider::CN()
		);
	}

	public function execute()
	{
		$request = $this->getRequest();

		$action = null;
		list($action) = $request->getActions(1);

		switch ($action) {

			case PaymentProvider::PROXY_URL_POSTFIX: {

					$this->executeProxyAction();
				} break;

			case PaymentProvider::PROVIDER_NOTIFICATION_URL_POSTFIX: {

					$this->executeProviderNotificationAction();
				} break;

			case PaymentProvider::CUSTOMER_RETURN_URL_POSTFIX: {

					$this->executeCustomerReturnAction();
				} break;

			default: {

					throw new Exception\ResourceNotFoundException('Could not determine action');
				}
		}
	}

}
