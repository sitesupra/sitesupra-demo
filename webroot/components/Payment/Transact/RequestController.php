<?php

namespace Project\Payment\Transact;

use Supra\Payment\RequestControllerAbstraction;

class RequestController extends RequestControllerAbstraction
{

	function __construct()
	{
		parent::__construct(
				Action\ProxyAction::CN(), Action\ProviderNotificationAction::CN(), Action\CustomerReturnAction::CN()
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
					break;
				}

			case PaymentProvider::CUSTOMER_RETURN_URL_POSTFIX: {

					$this->executeCustomerReturnAction();
					break;
				}

			case PaymentProvider::PROVIDER_NOTIFICATION_URL_POSTFIX: {

					$this->executeProviderNotificationAction();
					break;
				}

			default: {

					throw new Exception\RuntimeException('Bad request.');
				}
		}
	}

}
