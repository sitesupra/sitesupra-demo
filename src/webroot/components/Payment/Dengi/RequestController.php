<?php


/*
4205734305390295
11/13
Jevgenijs Harkovs
200

LV2167
Petera 3b
Marupe

37129211996 
it@videinfra.com
 *  * 
 * 
 */
namespace Project\Payment\Dengi;

use Supra\Payment\RequestControllerAbstraction;
use Supra\Response\HttpResponse;

class RequestController extends RequestControllerAbstraction
{

	const DENGI_FAILURE = 'failure';
	const DENGI_SUCCESS = 'success';
	const DENGI_STATUS_UPDATE = 'status-change';

	/**
	 * 
	 */
	function __construct()
	{
		parent::__construct(
				Action\ProxyAction::CN(), Action\ProviderNotificationAction::CN(), Action\CustomerReturnAction::CN()
		);
	}

	/**
	 * @throws Exception\RuntimeException
	 */
	public function execute()
	{
		\Log::error('$_REQUEST: ', $_REQUEST);

		$request = $this->getRequest();

		list($action) = $request->getActions(1);

		switch ($action) {

			case PaymentProvider::PROXY_URL_POSTFIX: {

					$this->executeProxyAction();

					break;
				}

			case self::DENGI_FAILURE: {

					$response = $this->getResponse();

					if ($response instanceof HttpResponse) {

						$errorMessages = $request->getParameter('err_msg');

						if (is_array($errorMessages)) {

							foreach ($errorMessages as $errorMessage) {
								\Log::error(iconv('windows-1251', 'utf-8', $errorMessage));
							}
						} else {

							\Log::error(iconv('windows-1251', 'utf-8', $errorMessages));
						}
						
						$this->executeCustomerReturnAction();
						//$response->redirect('/404');
					}

					break;
				}


			case self::DENGI_SUCCESS: {

					$this->executeCustomerReturnAction();

					break;
				}

			case self::DENGI_STATUS_UPDATE: {

					$this->executeProviderNotificationAction();

					break;
				}

			default: {

					throw new Exception\RuntimeException('Bad request.');
				}
		}
	}

}
