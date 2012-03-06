<?php

namespace Project\Payment\Transact;

use Supra\Payment\Provider\PaymentProviderAbstraction;
use Supra\Payment\Entity\Order;
use Supra\Payment\Entity\Transaction\Transaction;
use Supra\Payment\Order\OrderStatus;
use Supra\Payment\Order\RecurringOrderPeriodDimension;
use Supra\Payment\RecurringPayment\RecurringPaymentStatus;
use Supra\Locale\Locale;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Payment\Transaction\TransactionType;
use Supra\Payment\Entity\Order\RecurringPayment;
use Supra\Payment\Entity\RecurringPayment\RecurringPaymentProductItem;
use Supra\Payment\Entity\RecurringPayment\RecurringPaymentPaymentProviderItem;
use Supra\Response\ResponseInterface;
use Supra\Payment\PaymentEntityProvider;
use Supra\Payment\SearchPaymentEntityParameter;
use Supra\Payment\Order\OrderProvider;
use Supra\Session\SessionManager;
use Supra\Session\SessionNamespace;

class PaymentProvider extends PaymentProviderAbstraction
{
	const PHASE_NAME_INITIALIZE_TRANSACTION = 'transact-initializeTransaction';
	const PHASE_NAME_CHARGE_TRANSACTION = 'transact-chargeTransaction';
	const PHASE_NAME_STATUS_ON_RETURN = 'transact-statusOnReturn';
	const PHASE_NAME_STATUS_ON_NOTIFICATION = 'transact-statusOnNotification';

	const KEY_NAME_TRANSACT_TRANSACTION_ID = 'OK';
	const KEY_NAME_MERCHANT_TRANSACTION_ID = 'merchant_transaction_id';

	/**
	 * @var PaymentEntityProvider
	 */
	protected $paymentEntityProvider;

	/**
	 * @var OrderProvider
	 */
	protected $orderProvider;

	/**
	 * @var string
	 */
	protected $merchantGuid;

	/**
	 * @var string
	 */
	protected $password;

	/**
	 * @var string
	 */
	protected $routingString;

	/**
	 * @var string
	 */
	protected $returnHost;

	/**
	 *
	 * @var string
	 */
	protected $callbackHost;

	/**
	 * @var string
	 */
	protected $apiUrl;

	/**
	 * @var boolean
	 */
	protected $is3dAccount;

	/**
	 * @var boolean
	 */
	protected $gatewayCollects;

	/**
	 * @var string
	 */
	protected $formDataPath;

	/**
	 * @param string $merchantGuid 
	 */
	public function setMerchantGuid($merchantGuid)
	{
		$this->merchantGuid = $merchantGuid;
	}

	public function getMerchantGuid()
	{
		return $this->merchantGuid;
	}

	public function getPassword()
	{
		return $this->password;
	}

	public function getRoutingString()
	{
		return $this->routingString;
	}

	public function getReturnHost()
	{
		return $this->returnHost;
	}

	public function getCallbackHost()
	{
		return $this->callbackHost;
	}

	public function getIs3dAccount()
	{
		return $this->is3dAccount;
	}

	public function getGatewayCollects()
	{
		return $this->gatewayCollects;
	}

	public function getFormDataPath()
	{
		return $this->formDataPath;
	}

	public function setFormDataPath($formDataPath)
	{
		$this->formDataPath = $formDataPath;
	}

	public function setIs3dAccount($is3dAccount)
	{
		$this->is3dAccount = $is3dAccount;
	}

	public function setGatewayCollects($gatewayCollects)
	{
		$this->gatewayCollects = $gatewayCollects;
	}

	/**
	 * @param string $password
	 */
	public function setPassword($password)
	{
		$this->password = $password;
	}

	/**
	 * @return string
	 */
	public function getApiUrl()
	{
		return $this->apiUrl;
	}

	/**
	 * @param string $apiUrl 
	 */
	public function setApiUrl($apiUrl)
	{
		$this->apiUrl = $apiUrl;
	}

	/**
	 * @param string $routingString
	 */
	public function setRoutingstring($routingString)
	{
		$this->routingString = $routingString;
	}

	/**
	 * @param string $transactServiceUrl 
	 */
	public function setTransactApiUrl($transactApiUrl)
	{
		$this->transactApiUrl = $transactApiUrl;
	}

	/**
	 * @return string
	 */
	public function getTransactApiUrl($apiName)
	{
		$query = array('a' => $apiName);

		return $this->transactApiUrl . '?' . http_build_query($query);
	}

	/**
	 * @param string $returnHost 
	 */
	public function setReturnHost($returnHost)
	{
		$this->returnHost = $returnHost;
	}

	/**
	 * @param string $callbackHost 
	 */
	public function setCallbackHost($callbackHost)
	{
		$this->callbackHost = $callbackHost;
	}

	/**
	 * @return string
	 */
	public function getTransactRedirectUrl($queryData)
	{
		$queryString = http_build_query($queryData);

		return $this->transactRedirectUrl . '?' . $queryString;
	}

	/**
	 * @param string $transactRedirectUrl 
	 */
	public function setTransactRedirectUrl($transactRedirectUrl)
	{
		$this->transactRedirectUrl = $transactRedirectUrl;
	}

	/**
	 * @return string
	 */
	private function getNotificationUrl()
	{
		return $this->getCallbackHost() . $this->getBaseUrl() . '/' . self::PROVIDER_NOTIFICATION_URL_POSTFIX;
	}

	/**
	 * @return string
	 */
	public function getReturnUrl()
	{
		return $this->getReturnHost() . $this->getBaseUrl() . '/' . self::CUSTOMER_RETURN_URL_POSTFIX;
	}

	/**
	 * @param Order\Order $order
	 * @return string 
	 */
	public function getFormDataUrl(Order\Order $order)
	{
		$queryData = array(
			PaymentProviderAbstraction::REQUEST_KEY_ORDER_ID => $order->getId()
		);

		$formDataUrl = $this->getFormDataPath() . '?' . http_build_query($queryData);

		return $formDataUrl;
	}

	/**
	 * @param Order\Order $order
	 * @return SessionNamespace
	 */
	public function getSessionForOrder(Order\Order $order)
	{
		$sessionManager = ObjectRepository::getSessionManager($this);
		$session = $sessionManager->getSessionNamespace($this->getId() . $order->getId());

		return $session;
	}

	/**
	 * @param Order\Order $order 
	 */
	public function updateShopOrder(Order\ShopOrder $order)
	{
		$paymentProviderOrderItem = $order->getOrderItemByPayementProvider();

		if ($paymentProviderOrderItem->getPaymentProviderId() != $this->getId()) {

			$order->removeOrderItem($paymentProviderOrderItem);

			$paymentProviderOrderItem = $order->getOrderItemByPayementProvider($this->getId());
		}

		$paymentProviderOrderItem->setPrice($order->getTotalForProductItems() * 0.11);
	}

	/**
	 * @param Order\ShopOrder $order 
	 * @return boolean
	 */
	public function validateShopOrder(Order\ShopOrder $order)
	{
		if ($order->getTotalForProductItems() < 20.00) {
			throw new Exception\RuntimeException('Total is too small!!!');
		}

		return true;
	}

	/**
	 * @param Order\ShopOrder $order
	 * @param ResponseInterface $response 
	 */
	public function processShopOrder(Order\ShopOrder $order, ResponseInterface $response)
	{
		parent::processShopOrder($order, $response);

		// This is Transact specific behaviour.
		$proxyActionUrlQueryData = array(
			self::REQUEST_KEY_ORDER_ID => $order->getId()
		);

		$this->redirectToProxy($proxyActionUrlQueryData, $response);
	}

	/**
	 * @param Order\RecurringOrder $order
	 * @param ResponseInterface $response 
	 */
	public function processRecurringOrder(Order\RecurringOrder $order, ResponseInterface $response)
	{
		parent::processRecurringOrder($order, $response);

		// This is Transact specific behaviour.
		$proxyActionUrlQueryData = array(
			self::REQUEST_KEY_ORDER_ID => $order->getId()
		);

		$this->redirectToProxy($proxyActionUrlQueryData, $response);
	}

	/**
	 * @param Order\Order $order
	 * @param Locale $locale 
	 * @return boolean
	 */
	public function getOrderItemDescription(Order\Order $order, Locale $locale = null)
	{
		return 'Transact fee - ' . ($order->getTotalForProductItems() * 0.10) . ' ' . $order->getCurrency()->getIso4217Code();
	}

	/**
	 * @param array
	 * @return string
	 */
	public function getCustomerReturnActionUrl($queryData)
	{
		$query = http_build_query($queryData);

		return $this->getReturnUrl() . '?' . $query;
	}

	/**
	 * @param array
	 * @return string
	 */
	public function getProviderNotificationActionUrl($queryData)
	{
		$query = http_build_query($queryData);

		return $this->getNotificationUrl() . '?' . $query;
	}

	/**
	 * @param string $apiName
	 * @param array $postData
	 * @return array 
	 */
	protected function callTransactApi($apiName, $postData)
	{
		$queryData = array('a' => $apiName);

		$apiUrl = $this->getApiUrl() . '?' . http_build_query($queryData);

		\Log::debug('callTransactApi URL: ', $apiUrl);

		$logData = $postData;

		if ( ! empty($logData['cc'])) {
			$logData['cc'] = '****************';
		}
		\Log::debug('callTransactApi POST: ', $logData);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $apiUrl);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_USERAGENT, 'cURL/PHP');
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		$rawResponse = curl_exec($ch);
		\Log::debug('callTransactApi RAW RESPONSE: ', $rawResponse);

		$curlError = curl_error($ch);
		if ( ! empty($curlError)) {
			\Log::debug('callTransactApi CURL ERROR: ', $curlError);
		}

		$response = $this->decodeTransactResponse($rawResponse);

		return $response;
	}

	/**
	 * @param string $rawResposne
	 * @return array
	 */
	protected function decodeTransactResponse($rawResposne)
	{
		$parts = explode('~', trim($rawResposne));

		$response = array();

		foreach ($parts as $part) {

			$name = null;
			$value = null;

			list($name, $value) = explode(':', trim($part), 2);

			$response[$name] = $value;
		}

		return $response;
	}

	/**
	 * @return array
	 */
	protected function getApiBaseData()
	{
		$apiData = array();

		$apiData['guid'] = $this->getMerchantGuid();
		$apiData['pwd'] = sha1($this->getPassword());
		$apiData['rs'] = $this->getRoutingString();

		return $apiData;
	}

	/**
	 * @param Order\Order $order
	 * @param array $postData
	 * @return array 
	 */
	protected function getInitializeTransactionData(Order\Order $order, $postData)
	{
		$apiData = $this->getApiBaseData();

		$apiData['merchant_transaction_id'] = $order->getPaymentEntityId();
		//$apiData['user_ip'] = '159.148.152.65'; //$_SERVER['REMOTE_ADDR'];
		$apiData['user_ip'] = $_SERVER['REMOTE_ADDR'];

		$description = array();
		foreach ($order->getProductItems() as $item) {
			/* @var $item Order\OrderProductItem */
			$description[] = $item->getDescription() . ' x' . $item->getQuantity();
		}
		$apiData['description'] = join(', ', $description);

		$apiData['amount'] = $order->getTotal() * 100;
		$apiData['currency'] = $order->getCurrency()->getIso4217Code();
		$apiData['name_on_card'] = $postData['name_on_card'];
		$apiData['street'] = $postData['street'];
		$apiData['zip'] = $postData['zip'];
		$apiData['city'] = $postData['city'];
		$apiData['country'] = $postData['country'];
		$apiData['state'] = $postData['state'] ? $postData['state'] : 'NA';
		$apiData['email'] = $postData['email'];
		$apiData['phone'] = $postData['phone'];
		$apiData['card_bin'] = substr($postData['cc'], 0, 6);
		$apiData['bin_name'] = $postData['bin_name'];
		$apiData['bin_phone'] = $postData['bin_phone'];

		$apiData['merchant_site_url'] = $this->getNotificationUrl();

		return $apiData;
	}

	/**
	 * @param Order\Order $order
	 * @param array $postData
	 * @return array 
	 */
	public function initializeRecurrentTransaction(Order\RecurringOrder $order, $postData)
	{
		$apiData = $this->getInitializeTransactionData($order, $postData);

		$apiData['save_card'] = 1;

		$result = $this->callTransactApi('init', $apiData);

		\Log::debug('TRANSACT INIT RECURRENT TRANSACTION RESULT: ', $result);

		return $result;
	}

	/**
	 * @param Order\Order $order
	 * @param array $postData
	 * @return array
	 */
	public function initializeTransaction(Order\ShopOrder $order, $postData)
	{
		$apiData = $this->getInitializeTransactionData($order, $postData);

		$result = $this->callTransactApi('init', $apiData);

		\Log::debug('TRANSACT INIT TRANSACTION RESULT: ', $result);

		return $result;
	}

	/**
	 * @param Order\Order $order
	 * @return string
	 */
	public function getProxyActionReturnFormDataUrl(Order\Order $order)
	{
		$queryData = array(
			Action\ProxyAction::REQUEST_KEY_RETURN_FROM_FORM => true,
			PaymentProviderAbstraction::REQUEST_KEY_ORDER_ID => $order->getId()
		);

		return $this->getProxyActionUrl($queryData);
	}

	/**
	 * @param Order\ShopOrder $order
	 * @param array $postData
	 * @return array
	 */
	public function chargeTransaction(Order\ShopOrder $order, $postData)
	{
		$transactTrascationId = $order->getPaymentEntityParameterValue(self::PHASE_NAME_INITIALIZE_TRANSACTION, self::KEY_NAME_TRANSACT_TRANSACTION_ID);

		$apiData = $this->getApiBaseData();

		$apiData['f_extended'] = 5;
		;
		$apiData['init_transaction_id'] = $transactTrascationId;
		$apiData['cc'] = $postData['cc'];
		$apiData['cvv'] = $postData['cvv'];
		$apiData['expire'] = $postData['expire'];

		$result = $this->callTransactApi('charge', $apiData);

		\Log::debug('TRANSACT CHARGE TRANSACTION RESULT: ', $result);

		return $result;
	}

	/**
	 * @param Order\Order $order
	 * @return array
	 */
	public function getTransactionStatus(Order\ShopOrder $order)
	{
		$transactTrascationId = $order->getPaymentEntityParameterValue(self::PHASE_NAME_INITIALIZE_TRANSACTION, self::KEY_NAME_TRANSACT_TRANSACTION_ID);

		$apiData = $this->getApiBaseData();

		$apiData['f_extended'] = 5;
		$apiData['init_transaction_id'] = $transactTrascationId;
		$apiData['request_type'] = 'transaction_status';

		$result = $this->callTransactApi('status_request', $apiData);

		\Log::debug('TRANSACT TRANSACTION STATUS RESULT: ', $result);

		return $result;
	}

	/**
	 * @param Order\RecurringOrder
	 * @param integer $amount
	 * @param string $description 
	 */
	public function initializeRecurrentPayment(Order\RecurringOrder $order, $amount, $description)
	{
		$apiData = $this->getApiBaseData();

		$originalTransactionInitId = $order->getPaymentEntityParameterValue(self::PHASE_NAME_INITIALIZE_TRANSACTION, self::KEY_NAME_TRANSACT_TRANSACTION_ID);

		$merchantTransactionId = $order->getRecurringPayment()
						->getLastTransaction()->getId();

		$apiData['original_init_id'] = $originalTransactionInitId;
		$apiData['merchant_transaction_id'] = $merchantTransactionId;
		$apiData['amount'] = intval($amount);
		$apiData['description'] = $description;

		$result = $this->callTransactApi('init_recurrent', $apiData);

		\Log::debug('TRANSACT INIT RECURRENT PAYMENT RESULT: ', $result);

		return $result;
	}

	/**
	 * @param Order\RecurringOrder $order
	 * @return array 
	 */
	public function chargeRecurringPayment(Order\RecurringOrder $order)
	{
		$lastRecurringTransaction = $order->getRecurringPayment()->getLastTransaction();
		$initTransactionId = $lastRecurringTransaction->getParameterValue(self::PHASE_NAME_INITIALIZE_RECURRENT, self::KEY_NAME_TRANSACT_TRANSACTION_ID);

		$apiData = $this->getApiBaseData();
		$apiData['init_transaction_id'] = $initTransactionId;

		$result = $this->callTransactApi('charge_recurrent', $apiData);

		\Log::debug('TRANSACT CHARGE RECURRENT PAYMENT RESULT: ', $result);

		return $result;
	}

	/**
	 * @param Order\RecurringOrder $order 
	 * @return array
	 */
	public function getRecurringPaymentStatus(Order\RecurringOrder $order)
	{
		$lastRecurringTransaction = $order->getRecurringPayment()->getLastTransaction();
		$transactTrascationId = $lastRecurringTransaction->getParameterValue(self::PHASE_NAME_INITIALIZE_RECURRENT, self::KEY_NAME_TRANSACT_TRANSACTION_ID);

		$apiData = $this->getApiBaseData();

		$apiData['f_extended'] = 5;
		$apiData['init_transaction_id'] = $transactTrascationId;
		$apiData['request_type'] = 'transaction_status';

		$result = $this->callTransactApi('status_request', $apiData);

		\Log::debug('TRANSACT RECURRENT PAYMENT STATUS RESULT: ', $result);
	}

	/**
	 * @return PaymentEntityProvider
	 */
	public function getPaymentEntityProvider()
	{
		if (empty($this->paymentEntityProvider)) {

			$em = $this->getEntityManager();

			$provider = new PaymentEntityProvider();
			$provider->setEntityManager($em);

			$this->paymentEntityProvider = $provider;
		}


		return $this->paymentEntityProvider;
	}

	/**
	 * @param PaymentEntityProvide $paymentEntityProvider 
	 */
	public function setPaymentEntityProvider(PaymentEntityProvide $paymentEntityProvider)
	{
		$this->paymentEntityProvider = $paymentEntityProvider;
	}

	/**
	 * @return OrderProvider
	 */
	public function getOrderProvider()
	{
		if (empty($this->orderProvider)) {

			$em = $this->getEntityManager();

			$provider = new OrderProvider();
			$provider->setEntityManager($em);

			$this->orderProvider = $provider;
		}


		return $this->orderProvider;
	}

	/**
	 * @param OrderProvier $orderProvider 
	 */
	public function setOrderProvider(PaymentEntityProvide $orderProvider)
	{
		$this->orderProvider = $orderProvider;
	}

	/**
	 * @param string $merchantTransactionId
	 * @return Order\Order
	 */
	public function getOrderFromMerchantTransactionId($merchantTransactionId)
	{
		$paymentEntityProvider = $this->getPaymentEntityProvider();
		$orderProvider = $this->getOrderProvider();

		$paymentEntity = $paymentEntityProvider->getEntiy($merchantTransactionId);

		$order = $orderProvider->getOrderByPaymentEntity($paymentEntity);

		return $order;
	}

	/**
	 * @param string $transactTransactionId
	 * @return Order\Order
	 */
	public function getOrderFromTransactTransactionId($transactTransactionId)
	{
		$paymentEntityProvider = $this->getPaymentEntityProvider();
		$orderProvider = $this->getOrderProvider();

		$paymentEntities = $paymentEntityProvider->findByParameterPhaseAndNameAndValue(self::PHASE_NAME_INITIALIZE_TRANSACTION, self::KEY_NAME_TRANSACT_TRANSACTION_ID, $transactTransactionId);
		
		if(count($paymentEntities) > 1) {
			throw new Exception\RuntimeException('Got more than one payment entity for Transact transaction id "' . $transactTransactionId . '".');
		}
		if(count($paymentEntities) == 0) {
			throw new Exception\RuntimeException('Did not find any payment entities for Transact transaction id "' . $transactTransactionId . '".');
		}
		
		$paymentEntity = array_pop($paymentEntities);
		
		$order = $orderProvider->getOrderByPaymentEntity($paymentEntity);

		return $order;
	}

}
