<?php

namespace Project\Payment\Transact;

use Supra\Payment\Provider\PaymentProviderAbstraction;
use Supra\Payment\Entity\Order;
use Supra\Payment\Entity\Order\ShopOrder;
use Supra\Payment\Entity\Order\RecurringOrder;
use Supra\Payment\Entity\Transaction\Transaction;
use Supra\Payment\Order\OrderStatus;
use Supra\Payment\Order\RecurringOrderPeriodDimension;
use Supra\Payment\RecurringPayment\RecurringPaymentStatus;
use Supra\Locale\LocaleInterface;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Payment\Transaction\TransactionType;
use Supra\Payment\Entity\RecurringPayment\RecurringPaymentProductItem;
use Supra\Payment\Entity\RecurringPayment\RecurringPaymentPaymentProviderItem;
use Supra\Response\ResponseInterface;
use Supra\Payment\PaymentEntityProvider;
use Supra\Payment\SearchPaymentEntityParameter;
use Supra\Payment\Order\OrderProvider;
use Supra\Session\SessionManager;
use Supra\Session\SessionNamespace;
use Supra\Payment\Entity\Abstraction\PaymentEntity;
use Supra\Payment\Entity\RecurringPayment\RecurringPayment;
use Supra\Payment\Entity\RecurringPayment\RecurringPaymentTransaction;
use Supra\Payment\Transaction\TransactionStatus;
use Supra\Response\TwigResponse;
use Supra\Controller\FrontController;

class PaymentProvider extends PaymentProviderAbstraction
{
	// Phase names used in Transact context

	const PHASE_NAME_INITIALIZE_TRANSACTION = 'transact-initialize';
	const PHASE_NAME_CHARGE_TRANSACTION = 'transact-charge';

	// Phase names for recurring payments
	const PHASE_NAME_INITIALIZE_RECURRING_TRANSACTION = 'transact-initializeRecurring';
	const PHASE_NAME_CHARGE_RECURRING_TRANSACTION = 'transact-chargeRecurring';

	// Phase name for refund status
	const PHASE_NAME_REFUND = 'transact-refund';

	// Phase names for transaction status storage
	const PHASE_NAME_STATUS_ON_RETURN = 'transact-statusOnReturn';
	const PHASE_NAME_STATUS_ON_NOTIFICATION = 'transact-statusOnNotification';

	// Misc. key names used in Transact context
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
	protected $recurrentRoutingString;

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
	protected $dataFormPath;

	/**
	 * @var string
	 */
	protected $userIpOverride;

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

	public function getRecurrentRoutingString()
	{
		return $this->recurrentRoutingString;
	}

	public function setRecurrentRoutingString($recurrentRoutingString)
	{
		$this->recurrentRoutingString = $recurrentRoutingString;
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

	public function getDataFormPath()
	{
		return $this->dataFormPath;
	}

	public function setDataFormPath($dataFormPath)
	{
		$this->dataFormPath = $dataFormPath;
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

	public function getApiUrl()
	{
		return $this->apiUrl;
	}

	public function setApiUrl($apiUrl)
	{
		$this->apiUrl = $apiUrl;
	}

	public function getUserIpOverride()
	{
		return $this->userIpOverride;
	}

	public function setUserIpOverride($userIpOverride)
	{
		$this->userIpOverride = $userIpOverride;
	}

	public function setRoutingstring($routingString)
	{
		$this->routingString = $routingString;
	}

	public function setTransactApiUrl($transactApiUrl)
	{
		$this->transactApiUrl = $transactApiUrl;
	}

	public function getTransactApiUrl($apiName)
	{
		$query = array('a' => $apiName);

		return $this->transactApiUrl . '?' . http_build_query($query);
	}

	public function setReturnHost($returnHost)
	{
		$this->returnHost = $returnHost;
	}

	public function setCallbackHost($callbackHost)
	{
		$this->callbackHost = $callbackHost;
	}

	public function getTransactRedirectUrl($queryData)
	{
		$queryString = http_build_query($queryData);

		return $this->transactRedirectUrl . '?' . $queryString;
	}

	public function setTransactRedirectUrl($transactRedirectUrl)
	{
		$this->transactRedirectUrl = $transactRedirectUrl;
	}

	private function getNotificationUrl()
	{
		return $this->getCallbackHost() . $this->getBaseUrl() . '/' . self::PROVIDER_NOTIFICATION_URL_POSTFIX;
	}

	public function getReturnUrl()
	{
		return $this->getReturnHost() . $this->getBaseUrl() . '/' . self::CUSTOMER_RETURN_URL_POSTFIX;
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
	 * @param Order\Order $order
	 * @return string 
	 */
	public function getDataFormUrl(Order\Order $order)
	{
		$queryData = array(
			PaymentProviderAbstraction::REQUEST_KEY_ORDER_ID => $order->getId()
		);

		$formDataUrl = $this->getDataFormPath() . '?' . http_build_query($queryData);

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
		//if ($order->getTotalForProductItems() < 20.00) {
		//	throw new Exception\RuntimeException('Total is too small!!!');
		//}

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
	 * @param Order\ShopOrder $order
	 * @param array $paymentCredentials
	 * @param ResponseInterface $response 
	 */
	public function processShopOrderDirect(Order\ShopOrder $order, $paymentCredentials)
	{
		$response = new \Supra\Response\HttpResponse();
		
		parent::processShopOrder($order, $response);
		
		$proxyActionQueryData = array(
			self::REQUEST_KEY_ORDER_ID => $order->getId(),
			Action\ProxyAction::REQUEST_KEY_RETURN_FROM_FORM => true
		);		
		
		$request = new \Supra\Request\HttpRequest();
		$request->setPost($paymentCredentials);
		$request->setQuery($proxyActionQueryData);
		
		$lastRouter = new \Supra\Payment\PaymentProviderUriRouter();
		$lastRouter->setPaymentProvider($this);
		
		$request->setLastRouter($lastRouter);

		$proxyActionController = FrontController::getInstance()->runController(Action\ProxyAction::CN(), $request);
		
		$response = $proxyActionController->getResponse();
		
		return $response;
	}
	

	/**
	 * @param Order\ShopOrder $order
	 * @param array $paymentCredentials
	 * @param ResponseInterface $response 
	 */
	public function processRecurringOrderDirect(Order\RecurringOrder $order, $paymentCredentials)
	{
		$response = new \Supra\Response\HttpResponse();
		
		parent::processRecurringOrder($order, $response);
		
		$proxyActionQueryData = array(
			self::REQUEST_KEY_ORDER_ID => $order->getId(),
			Action\ProxyAction::REQUEST_KEY_RETURN_FROM_FORM => true
		);		
		
		$request = new \Supra\Request\HttpRequest();
		$request->setPost($paymentCredentials);
		$request->setQuery($proxyActionQueryData);
		
		$lastRouter = new \Supra\Payment\PaymentProviderUriRouter();
		$lastRouter->setPaymentProvider($this);
		
		$request->setLastRouter($lastRouter);

		$proxyActionController = FrontController::getInstance()->runController(Action\ProxyAction::CN(), $request);
		
		$response = $proxyActionController->getResponse();
		
		return $response;
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
	 * @param Order\RecurringOrder $order
	 * @param float $newAmount
	 * @param string $newDescription
	 * @throws Exception\RuntimeException 
	 */
	public function processNextRecurringOrderTransaction(Order\RecurringOrder $order, $newAmount = null, $newDescription = null)
	{
		$orderProvider = $this->getOrderProvider();

		$recurringPayment = $order->getRecurringPayment();

		$initialTransaction = $recurringPayment->getInitialTransaction();

		if (empty($initialTransaction) || $initialTransaction->getStatus() != TransactionStatus::SUCCESS) {
			throw new Exception\RuntimeException('Initial transaction not completed.');
		}

		$initializeResult = $this->initializeRecurringTransaction($order, $newAmount, $newDescription);
		$orderProvider->store($order);

		if ( ! empty($initializeResult['ERROR'])) {

			throw new Exception\RuntimeException('Could not start next recurring transaction.');
		} else if ( ! empty($initializeResult['RedirectOnsite'])) {

			throw new Exception\RuntimeException('Received redirect URL for recurrent transaction.');
		}

		$chargeResult = $this->chargeLastRecurringTransaction($order);

		$orderProvider->store($order);

		$this->updateRecurringOrderStatus($order, $chargeResult);

		$orderProvider->store($order);
	}

	/**
	 * @param Order\RecurringOrder $order
	 * @param array $transactionStatus
	 * @throws Exception\RuntimeException 
	 */
	public function updateRecurringOrderStatus(Order\RecurringOrder $order, $transactionStatus)
	{
		$recurringPayment = $order->getRecurringPayment();

		$initialTransaction = $recurringPayment->getInitialTransaction();

		$lastTransaction = $recurringPayment->getLastTransaction();

		if (empty($transactionStatus) || empty($transactionStatus['Status'])) {
			throw new Exception\RuntimeException('No transaction status.');
		}

		switch (strtolower($transactionStatus['Status'])) {

			case 'success': {
					$lastTransaction->setStatus(TransactionStatus::SUCCESS);

					$order->getRecurringPayment()
							->setStatus(RecurringPaymentStatus::PAID);
				} break;

			case 'failed': {
					$lastTransaction->setStatus(TransactionStatus::FAILED);

					if ($lastTransaction->getId() == $initialTransaction->getId()) {
						$recurringPaymentStatus = RecurringPaymentStatus::INITIAL_TRANSACTION_FAILED;
					} else {
						$recurringPaymentStatus = RecurringPaymentStatus::LAST_TRANSACTION_FAILED;
					}

					$order->getRecurringPayment()
							->setStatus($recurringPaymentStatus);
				} break;

			case 'pending': {

					throw new Exception\RuntimeException('Pending transaction handling not implemented yet.');
				} break;

			default: {

					throw new Exception\RuntimeException('Transaction status "' . $transactionStatus['Status'] . '" is not recognized.');
				}
		}
	}

	/**
	 * @param Order\ShopOrder $order
	 * @param array $transactionStatus
	 * @throws Exception\RuntimeException 
	 */
	public function updateShopOrderStatus(Order\ShopOrder $order, $transactionStatus)
	{
		if (empty($transactionStatus) || empty($transactionStatus['Status'])) {
			throw new Exception\RuntimeException('No transaction status.');
		}

		switch (strtolower($transactionStatus['Status'])) {

			case 'success': {
					$order->getTransaction()
							->setStatus(TransactionStatus::SUCCESS);
				} break;

			case 'failed': {
					$order->getTransaction()
							->setStatus(TransactionStatus::FAILED);
				} break;

			case 'pending': {

					throw new Exception\RuntimeException('Pending transaction handling not implemented yet.');
				} break;

			default: {

					throw new Exception\RuntimeException('Transaction status "' . $transactionStatus['Status'] . '" is not recognized.');
				}
		}
	}

	/**
	 * @param Order\Order $order
	 * @param LocaleInterface $locale
	 * @return boolean
	 */
	public function getOrderItemDescription(Order\Order $order, LocaleInterface $locale = null)
	{
		return 'Transact processing fee';
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
	 * @param PaymentEntity $paymentEntity
	 * @return string
	 * @throws Exception\RuntimeException 
	 */
	public function getTransactTransactionIdFromPaymentEntity(PaymentEntity $paymentEntity)
	{
		$phaseName = null;

		if ($paymentEntity instanceof Transaction) {

			$phaseName = self::PHASE_NAME_INITIALIZE_TRANSACTION;
		} else if ($paymentEntity instanceof RecurringPaymentTransaction) {

			$phaseName = self::PHASE_NAME_INITIALIZE_RECURRING_TRANSACTION;
		} else {
			throw new Exception\RuntimeException('Do not know how to get Transact transaction id from payment entity of type "' . get_class($paymentEntity) . '".');
		}

		$transactTransactionId = $paymentEntity->getParameterValue($phaseName, self::KEY_NAME_TRANSACT_TRANSACTION_ID);

		if (empty($transactTransactionId)) {
			throw new Exception\RuntimeException('Could not find Transact transaction id from payment entity "' . $paymentEntity->getId() . '".');
		}

		return $transactTransactionId;
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
			throw new Exception\RuntimeException('Transact API request failed: ' . $curlError);
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
	 * @return string
	 */
	private function getUserIp()
	{
		$userIp = $this->getUserIpOverride();

		if (empty($userIp)) {
			$userIp = $_SERVER['REMOTE_ADDR'];
		}

		return $userIp;
	}

	/**
	 * @param Order\Order $order
	 * @param array $postData
	 * @return array 
	 */
	protected function getInitializeTransactTransactionData(Order\Order $order, $merchantTransactionId, $postData)
	{
		$apiData = $this->getApiBaseData();

		$apiData['merchant_transaction_id'] = $merchantTransactionId;

		$apiData['user_ip'] = $this->getUserIp();

		$description = array();
		foreach ($order->getProductItems() as $item) {
			/* @var $item Order\OrderProductItem */
			$description[] = $item->getDescription() . ' x' . $item->getQuantity();
		}
		$apiData['description'] = join(', ', $description);

		$apiData['amount'] = $order->getTotal() * 100;
		$apiData['currency'] = $order->getCurrency()->getIso4217Code();

		$apiData['merchant_site_url'] = $order->getInitiatorUrl();

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

		return $apiData;
	}

	/**
	 * @param Order\Order $order
	 * @param array $postData
	 * @return array
	 */
	public function initializeTransaction(Order\ShopOrder $order, $postData)
	{
		$transaction = $order->getTransaction();
		/* @var $transaction Transaction */

		$apiData = $this->getInitializeTransactTransactionData($order, $transaction->getId(), $postData);

		$result = $this->callTransactApi('init', $apiData);

		\Log::debug('TRANSACT INIT TRANSACTION RESULT: ', $result);

		$transaction->addToParameters(self::PHASE_NAME_INITIALIZE_TRANSACTION, $postData);
		$transaction->addToParameters(self::PHASE_NAME_INITIALIZE_TRANSACTION, $result);

		return $result;
	}

	/**
	 * @param Order\ShopOrder $order
	 * @param array $postData
	 * @return array
	 */
	public function chargeTransaction(Order\ShopOrder $order, $postData)
	{
		$transaction = $order->getTransaction();

		$transactTransactionId = $this->getTransactTransactionIdFromPaymentEntity($transaction);

		$apiData = $this->getApiBaseData();

		$apiData['f_extended'] = 5;

		$apiData['init_transaction_id'] = $transactTransactionId;

		$apiData['cc'] = $postData['cc'];
		$apiData['cvv'] = $postData['cvv'];
		$apiData['expire'] = $postData['expire'];

		$result = $this->callTransactApi('charge', $apiData);

		\Log::debug('TRANSACT CHARGE TRANSACTION RESULT: ', $result);

		$transaction->addToParameters(self::PHASE_NAME_CHARGE_TRANSACTION, $result);

		return $result;
	}

	/**
	 * @param PaymentEntity $paymentEntity
	 * @return array
	 */
	public function getTransactTransactionStatus(PaymentEntity $paymentEntity)
	{
		$transactTransactionId = $this->getTransactTransactionIdFromPaymentEntity($paymentEntity);

		$result = $this->getTransactTransactionStatusForId($transactTransactionId);

		return $result;
	}

	/**
	 * @param string $transactTransactionId
	 * @return array
	 */
	protected function getTransactTransactionStatusForId($transactTransactionId)
	{
		$apiData = $this->getApiBaseData();

		$apiData['f_extended'] = 5;

		$apiData['init_transaction_id'] = $transactTransactionId;
		$apiData['request_type'] = 'transaction_status';

		$result = $this->callTransactApi('status_request', $apiData);

		\Log::debug('TRANSACT TRANSACTION STATUS RESULT: ', $result);

		return $result;
	}

	/**
	 * @param Order\Order $order 
	 */
	public function issueRefundForOrder(Order\Order $order, $refundAmount = false)
	{
		$orderProvider = $this->getOrderProvider();

		if ($order instanceof ShopOrder) {
			$this->issueRefundForShopOrder($order, $refundAmount);
		} else if ($order instanceof Order\RecurringOrder) {
			$this->issueRefundForRecurringOrder($order, $refundAmount);
		} else {
			throw new Exception\RuntimeException('Do not know how to refund "' . get_class($order) . '" ');
		}

		$orderProvider->store($order);
	}

	/**
	 * @param ShopOrder $order
	 * @param float $amount
	 * @throws Exception\RuntimeException 
	 */
	protected function issueRefundForShopOrder(ShopOrder $order, $refundAmount)
	{
		$transaction = $order->getTransaction();

		if ($transaction->getStatus() != TransactionStatus::SUCCESS) {
			throw new Exception\RuntimeException('Only successful transactions can be refunded');
		}

		$transactTransactionId = $this->getTransactTransactionIdFromPaymentEntity($transaction);

		if ($refundAmount === false) {
			$refundAmount = $transaction->getAmount();
		}

		$result = $this->issueRefundForTransactTransactionId($transactTransactionId, $refundAmount);

		$transaction->addToParameters(self::PHASE_NAME_REFUND, $result);

		$transaction->setStatus(TransactionStatus::REFUNDED);
	}

	/**
	 * @param RecurringOrder $order
	 * @param float $refundAmount
	 * @throws Exception\RuntimeException 
	 */
	protected function issueRefundForRecurringOrder(RecurringOrder $order, $refundAmount)
	{
		$recurringPayment = $order->getRecurringPayment();

		$lastTransaction = $recurringPayment->getLastTransaction();

		if ($lastTransaction->getStatus() != TransactionStatus::SUCCESS) {
			throw new Exception\RuntimeException('Only successful transactions can be refunded');
		}

		$transactTransactionId = $this->getTransactTransactionIdFromPaymentEntity($lastTransaction);

		if ($refundAmount === false) {
			$refundAmount = $lastTransaction->getAmount();
		}

		$refundResult = $this->issueRefundForTransactTransactionId($transactTransactionId, $refundAmount);

		$lastTransaction->addToParameters(self::PHASE_NAME_REFUND, $refundResult);

		$lastTransaction->setStatus(TransactionStatus::REFUNDED);
	}

	/**
	 * @param string $transactTransactionId
	 * @param float $amount 
	 * @return array
	 */
	protected function issueRefundForTransactTransactionId($transactTransactionId, $amount)
	{
		$apiData = $this->getApiBaseData();

		$apiData['init_transaction_id'] = $transactTransactionId;
		$apiData['amount_to_refund'] = $amount * 100;

		$result = $this->callTransactApi('refund', $apiData);

		\Log::debug('TRANSACT REFUND RESULT: ', $result);

		return $result;
	}

	/**
	 * @param Order\Order $order
	 * @param array $postData
	 * @return array 
	 */
	public function initializeRecurringPayment(Order\RecurringOrder $order, $postData)
	{
		$recurringPayment = $order->getRecurringPayment();
		/* @var $recurringPayment RecurringPayment */

		$transaction = new RecurringPaymentTransaction();

		$transaction->setStatus(TransactionStatus::STARTED);
		$transaction->setAmount($order->getTotal());
		$transaction->setDescription($order->getBillingDescription());

		$recurringPayment->addTransaction($transaction);

		$apiData = $this->getInitializeTransactTransactionData($order, $transaction->getId(), $postData);

		$apiData['save_card'] = 1;

		$result = $this->callTransactApi('init', $apiData);

		\Log::debug('TRANSACT INIT RECURRENT TRANSACTION RESULT: ', $result);

		$recurringPayment->addToParameters(self::PHASE_NAME_INITIALIZE_RECURRING_TRANSACTION, $postData);

		$transaction->addToParameters(self::PHASE_NAME_INITIALIZE_RECURRING_TRANSACTION, $result);

		return $result;
	}

	/**
	 * @param Order\RecurringOrder
	 * @param float $amount
	 * @param string $description 
	 */
	public function initializeRecurringTransaction(Order\RecurringOrder $order, $newAmount = null, $newDescription = null)
	{
		$recurringPayment = $order->getRecurringPayment();
		/* @var $recurringPayment RecurringPayment */

		$initialTransaction = $recurringPayment->getInitialTransaction();
		$initialTransacTransactionId = $this->getTransactTransactionIdFromPaymentEntity($initialTransaction);

		$transaction = new RecurringPaymentTransaction();

		if (empty($newAmount)) {
			$amount = $order->getTotal();
		} else {
			$amount = $newAmount;
		}

		$transaction->setAmount($amount);

		if (empty($newDescription)) {
			$description = $order->getBillingDescription();
		} else {
			$description = $newDescription;
		}

		$transaction->setDescription($description);
		$transaction->setStatus(TransactionStatus::STARTED);
		$recurringPayment->addTransaction($transaction);

		$merchantTransactionId = $transaction->getId();

		$apiData = $this->getApiBaseData();

		$apiData['rs'] = $this->getRecurrentRoutingString();

		$apiData['original_init_id'] = $initialTransacTransactionId;
		$apiData['merchant_transaction_id'] = $merchantTransactionId;
		$apiData['amount'] = intval($amount * 100);
		$apiData['description'] = $description;

		$result = $this->callTransactApi('init_recurrent', $apiData);

		\Log::debug('TRANSACT INIT RECURRENT TRANSACTION RESULT: ', $result);

		$transaction->addToParameters(self::PHASE_NAME_INITIALIZE_RECURRING_TRANSACTION, $result);

		return $result;
	}

	/**
	 * @param Order\RecurringOrder $order
	 * @return array 
	 */
	public function chargeInitialRecurringTransaction(Order\RecurringOrder $order, $postData)
	{
		$recurringPayment = $order->getRecurringPayment();

		$initialTransaction = $recurringPayment->getInitialTransaction();

		$transactTransactionId = $this->getTransactTransactionIdFromPaymentEntity($initialTransaction);

		$apiData = $this->getApiBaseData();

		$apiData['init_transaction_id'] = $transactTransactionId;
		$apiData['f_extended'] = 5;

		$apiData['cc'] = $postData['cc'];
		$apiData['cvv'] = $postData['cvv'];
		$apiData['expire'] = $postData['expire'];

		$result = $this->callTransactApi('charge', $apiData);

		\Log::debug('TRANSACT CHARGE INITIAL RECURRENT TRANSACTION RESULT: ', $result);
		$initialTransaction->addToParameters(self::PHASE_NAME_CHARGE_RECURRING_TRANSACTION, $result);

		return $result;
	}

	/**
	 * @param Order\RecurringOrder $order
	 * @return array 
	 */
	public function chargeLastRecurringTransaction(Order\RecurringOrder $order)
	{
		$recurringPayment = $order->getRecurringPayment();

		$lastRecurringTransaction = $recurringPayment->getLastTransaction();

		$recurringTransactTransactionId = $this->getTransactTransactionIdFromPaymentEntity($lastRecurringTransaction);

		$apiData = $this->getApiBaseData();

		$apiData['rs'] = $this->getRecurrentRoutingString();

		$apiData['init_transaction_id'] = $recurringTransactTransactionId;
		$apiData['f_extended'] = 5;

		$result = $this->callTransactApi('charge_recurrent', $apiData);

		\Log::debug('TRANSACT CHARGE RECURRENT TRANSACTION RESULT: ', $result);
		$lastRecurringTransaction->addToParameters(self::PHASE_NAME_CHARGE_RECURRING_TRANSACTION, $result);

		return $result;
	}

	/**
	 * @param Order\RecurringOrder $order 
	 * @return array
	 */
	public function getLastRecurringTransactionStatus(Order\RecurringOrder $order)
	{
		$recurringPayment = $order->getRecurringPayment();

		$initialTransaction = $recurringPayment->getIntialTransaction();
		$lastTransaction = $order->getRecurringPayment()->getLastTransaction();

		$transactTrascationId = $this->getTransactTransactionIdFromPaymentEntity($lastTransaction);

		$apiData = $this->getApiBaseData();

		$apiData['f_extended'] = 5;

		if ($lastTransaction->getId() != $initialTransaction->getId()) {
			$apiData['rs'] = $this->getRecurrentRoutingString();
		}

		$apiData['init_transaction_id'] = $transactTrascationId;
		$apiData['request_type'] = 'transaction_status';

		$result = $this->callTransactApi('status_request', $apiData);

		\Log::debug('TRANSACT RECURRENT PAYMENT STATUS RESULT: ', $result);
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

		$paymentEntities = $paymentEntityProvider->findByParameterNameAndValue(self::PHASE_NAME_INITIALIZE_TRANSACTION, self::KEY_NAME_TRANSACT_TRANSACTION_ID, $transactTransactionId);

		if (count($paymentEntities) > 1) {
			throw new Exception\RuntimeException('Got more than one payment entity for Transact transaction id "' . $transactTransactionId . '".');
		}
		if (count($paymentEntities) == 0) {
			throw new Exception\RuntimeException('Did not find any payment entities for Transact transaction id "' . $transactTransactionId . '".');
		}

		$paymentEntity = array_pop($paymentEntities);

		$order = $orderProvider->getOrderByPaymentEntity($paymentEntity);

		return $order;
	}

	/**
	 * @param Order\Order $order
	 * @throws Exception\RuntimeException 
	 */
	public function refundTransaction(Order\Order $order)
	{
		if ($order instanceof Order\ShopOrder) {

			$this->refundShopOrder($order);
		} else if ($order instanceof Order\RecurringOrder) {

			$this->refundRecurringOrder($order);
		} else {
			throw new Exception\RuntimeException('Do not know how to do a refund for "' . get_class($order) . '" order type.');
		}
	}

}
