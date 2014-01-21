<?php

namespace Project\Payment\Paypal;

use PayPal\Auth\Oauth\AuthSignature;
use Supra\Payment\Entity\Currency\Currency;
use Supra\Payment\Entity\Order\ShippingOrderItem;
use Supra\Payment\Entity\Order\TaxOrderItem;
use Supra\Payment\Entity\RecurringPayment\RecurringPayment;
use Supra\Payment\Provider\Exception\ConfigurationException;
use Supra\Payment\Provider\PaymentProviderAbstraction;
use Supra\Payment\Entity\Order\OrderPaymentProviderItem;
use Supra\Payment\Entity\Order\Order;
use Supra\Payment\Entity\Order\ShopOrder;
use Supra\Payment\Entity\Order\RecurringOrder;
use Supra\Payment\Entity\Order\OrderItem;
use Supra\Payment\Order\OrderStatus;
use Supra\Payment\Entity\Order\OrderProductItem;
use Supra\Locale\LocaleInterface;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Payment\Entity\Transaction\Transaction;
use Supra\Payment\Transaction\TransactionStatus;
use Supra\Payment\Transaction\TransactionType;
use Supra\Payment\RecurringPayment\RecurringPaymentStatus;

//use Supra\Payment\Entity\Order\RecurringPayment;
use Supra\Payment\Entity\RecurringPayment\RecurringPaymentProductItem;
use Supra\Payment\Entity\RecurringPayment\RecurringPaymentPaymentProviderItem;
use Supra\Response\ResponseInterface;
use Supra\Payment\Order\RecurringOrderPeriodDimension;

/**
 * Class PaymentProvider
 * @package Project\Payment\Paypal
 */
class PaymentProvider extends PaymentProviderAbstraction
{
	/**
	 *
	 */
	const REQUEST_KEY_TOKEN = 'TOKEN';

	/**
	 *
	 */
	const TRANSACTION_PARAMETER_NAME_TOKEN = 'TOKEN';

	/**
	 *
	 */
	const TRANSACTION_PARAMETER_TRANSACTIONID = 'PAYMENTINFO_0_TRANSACTIONID';

	/**
	 *
	 */
	const TRANSACTION_PARAMETER_PROFILEID = 'PROFILEID';

	/**
	 *
	 */
	const PHASE_NAME_PAYPAL_SET_EXPRESS_CHECKOUT = 'paypal-SetExpressCheckout';

	/**
	 *
	 */
	const PHASE_NAME_CHECKOUT_DETAILS = 'paypal-CheckoutDetails';

	/**
	 *
	 */
	const PHASE_NAME_DO_PAYMENT = 'paypal-DoPayment';

	/**
	 *
	 */
	const PHASE_NAME_CREATE_RECURRING_PAYMENT = 'paypal-CreateRecurringPaymentsProfile';

	/**
	 *
	 */
	const PHASE_NAME_IPN = 'paypal-ipn-';

	/**
	 *
	 */
	const PHASE_NAME_REFUND_TRANSACTION = 'paypal-RefundTransaction';

	/**
	 *
	 */
	const CUSTOMER_RETURN_ACTION = 'return';

	/**
	 *
	 */
	const CUSTOMER_RETURN_ACTION_RETURN = 'return';

	/**
	 *
	 */
	const CUSTOMER_RETURN_ACTION_CANCEL = 'cancel';

	/**
	 *
	 */
	const CUSTOMER_RETURN_SUFFIX_SHOP = 'shop';

	/**
	 *
	 */
	const CUSTOMER_RETURN_SUFFIX_RECURRING = 'recurring';

	/**
	 *
	 */
	const EVENT_PAYER_CHECKOUT_DETAILS = 'paypalPayerCheckoutDetails';

	/**
	 *
	 */
	const REQUEST_KEY_SHOP_ORDER_ID = 'shopo';

	/**
	 *
	 */
	const REQUEST_KEY_RECURRING_ORDER_ID = 'recurro';

	/**
	 * @var string
	 */
	protected $apiUsername;

	/**
	 * @var string
	 */
	protected $apiPassword;

	/**
	 * @var string
	 */
	protected $apiSignature;

	/**
	 * @var string
	 */
	protected $xPaypalAuthorizationHeaderValue;

	/**
	 * @var string
	 */
	protected $useXPaypalAuthorizationHeader;

	/**
	 * @var string
	 */
	protected $accessToken;

	/**
	 * @var string
	 */
	protected $accessTokenSecret;

	/**
	 * @var string
	 */
	protected $accessSubject;

	/**
	 * @var string
	 */
	protected $paypalApiUrl;

	/**
	 * @var string
	 */
	protected $paypalApiUrl2;

	/**
	 * @var string
	 */
	protected $paypalRedirectUrl;

	/**
	 * @var
	 */
	protected $returnHost;

	/**
	 * @var
	 */
	protected $callbackHost;

	/**
	 * @var string
	 */
	protected $applicationId;

	/**
	 * @param string $apiUsername
	 */
	public function setApiUsername($apiUsername)
	{
		$this->apiUsername = $apiUsername;
	}


	/**
	 * @param string $apiPassword
	 */
	public function setApiPassword($apiPassword)
	{
		$this->apiPassword = $apiPassword;
	}

	/**
	 * @param string $apiSignature
	 */
	public function setApiSignature($apiSignature)
	{
		$this->apiSignature = $apiSignature;
	}

	/**
	 * @param $paypalApiUrl
	 */
	public function setPaypalApiUrl($paypalApiUrl)
	{
		$this->paypalApiUrl = $paypalApiUrl;
	}

	/**
	 * @return string
	 */
	public function getPaypalApiUrl()
	{
		return $this->paypalApiUrl;
	}

	/**
	 * @param string $paypalApiUrl2
	 */
	public function setPaypalApiUrl2($paypalApiUrl2)
	{
		$this->paypalApiUrl2 = $paypalApiUrl2;
	}

	/**
	 * @return string
	 */
	public function getPaypalApiUrl2()
	{
		return $this->paypalApiUrl2;
	}


	/**
	 * @param $queryData
	 * @return string
	 */
	public function getPaypalRedirectUrl($queryData)
	{
		$queryString = http_build_query($queryData);

		return $this->paypalRedirectUrl . '?' . $queryString;
	}

	/**
	 * @param string $paypalRedirectUrl
	 */
	public function setPaypalRedirectUrl($paypalRedirectUrl)
	{
		$this->paypalRedirectUrl = $paypalRedirectUrl;
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
	 * @param string $applicationId
	 */
	public function setApplicationId($applicationId)
	{
		$this->applicationId = $applicationId;
	}

	/**
	 * @return bool
	 */
	public function getUseXPaypalAuthorizationHeader()
	{
		return $this->useXPaypalAuthorizationHeader;
	}

	/**
	 * @param bool $value
	 */
	public function setUseXPaypalAuthorizationHeader($value)
	{
		$this->useXPaypalAuthorizationHeader = $value;
	}

	/**
	 * @throws ConfigurationException
	 */
	protected function getAccessToken()
	{
		if (empty($this->accessToken)) {
			throw new ConfigurationException('PayPal Authorization Access Token Not Set');
		}

		return $this->accessToken;
	}

	/**
	 * @throws ConfigurationException
	 */
	protected function getAccessTokenSecret()
	{
		if (empty($this->accessTokenSecret)) {
			throw new ConfigurationException('PayPal Authorization Access Token Secret Not Set');
		}

		return $this->accessTokenSecret;
	}

	/**
	 * @param string $accessSubject
	 */
	public function setAccessSubject($accessSubject)
	{
		$this->accessSubject = $accessSubject;
	}

	/**
	 * @return string
	 */
	public function getAccessSubject()
	{
		return $this->accessSubject;
	}


	/**
	 * @param string|null $urlForAuthString
	 * @return array
	 */
	protected function getBaseApiData($urlForAuthString = null)
	{
		$apiData = array();
		$apiData['VERSION'] = '82.0';
		$apiData['USER'] = $this->apiUsername;
		$apiData['PWD'] = $this->apiPassword;
		$apiData['SIGNATURE'] = $this->apiSignature;

		if ($this->getUseXPaypalAuthorizationHeader()) {

			if (empty($urlForAuthString)) {
				$urlForAuthString = $this->getPaypalApiUrl();
			}

			$apiData['___HEADERS']['X-PAYPAL-AUTHORIZATION'] = $this->getXPaypalAuthorizationHeaderValue($urlForAuthString);

			if ($accessSubject = $this->getAccessSubject()) {
				$apiData['___HEADERS']['X-PAYPAL-SECURITY-SUBJECT'] = $accessSubject;
				$apiData['SUBJECT'] = $accessSubject;
			}
		}

		return $apiData;
	}

	/**
	 * @return string
	 */
	private function getNotificationUrl()
	{
		return $this->callbackHost . $this->getBaseUrl() . '/' . self::PROVIDER_NOTIFICATION_URL_POSTFIX;
	}

	/**
	 * @param Order $order
	 * @return array
	 * @throws Exception\RuntimeException
	 */
	protected function getSetExpressCheckoutApiData(Order $order)
	{
		$url = $this->getPaypalApiUrl();

		$apiData = $this->getBaseApiData($url);

		$apiData['METHOD'] = 'SetExpressCheckout';

		$apiData['___URL'] = $url;

		$urlSuffix = null;

		if ($order instanceof ShopOrder) {
			$urlSuffix = self::CUSTOMER_RETURN_SUFFIX_SHOP;
		} else if ($order instanceof RecurringOrder) {
			$urlSuffix = self::CUSTOMER_RETURN_SUFFIX_RECURRING;
		} else {
			throw new Exception\RuntimeException('Do not know how to proxy this type of order.');
		}

		$apiData['RETURNURL'] = $this->returnHost . $this->getBaseUrl() . '/' . self::CUSTOMER_RETURN_ACTION . '/' . self::CUSTOMER_RETURN_ACTION_RETURN . '/' . $urlSuffix;
		$apiData['CANCELURL'] = $this->returnHost . $this->getBaseUrl() . '/' . self::CUSTOMER_RETURN_ACTION . '/' . self::CUSTOMER_RETURN_ACTION_CANCEL . '/' . $urlSuffix;
		$apiData['NOTIFYURL'] = $this->getNotificationUrl() . '/' . $urlSuffix;

		$extraOptionsForPaymentProvider = $order->getExtraOptionsForPaymentProvider();
		if (isset($extraOptionsForPaymentProvider['solution_type'])) {
			$apiData['SOLUTIONTYPE'] = $extraOptionsForPaymentProvider['solution_type'];
		}
		if (isset($extraOptionsForPaymentProvider['landing_page'])) {
			$apiData['LANDINGPAGE'] = $extraOptionsForPaymentProvider['landing_page'];
		}

		$orderItems = $order->getItems();

		$counter = 0;
		$totalItemQuantity = 0;
		$totalItemAmount = 0;
		$totalAmount = 0;

		foreach ($orderItems as $orderItem) {
			/* @var $orderItem OrderItem */

			$itemData = array();

			if ($orderItem instanceof OrderProductItem) {
				/* @var $orderItem OrderProductItem */

				$counter = intval($counter);

				$itemData = array(
					'L_PAYMENTREQUEST_0_AMT' . $counter => $orderItem->getPrice() / $orderItem->getQuantity(),
					'L_PAYMENTREQUEST_0_DESC' . $counter => $orderItem->getDescription(),
					'L_PAYMENTREQUEST_0_QTY' . $counter => $orderItem->getQuantity()
				);

				$totalItemAmount += $orderItem->getPrice();

				$totalAmount += $orderItem->getPrice();

				$totalItemQuantity += $orderItem->getQuantity();
			} else if ($orderItem instanceof OrderPaymentProviderItem) {

				$apiData['PAYMENTREQUEST_0_HANDLINGAMT'] = $orderItem->getPrice();
			} else if ($orderItem instanceof ShippingOrderItem) {

				$apiData['PAYMENTREQUEST_0_SHIPPINGAMT'] = $orderItem->getPrice();
			} else if ($orderItem instanceof TaxOrderItem) {

				$apiData['PAYMENTREQUEST_0_TAXAMT'] = $orderItem->getPrice();
			}

			$counter++;

			$apiData = $apiData + $itemData;
		}

		if ($order instanceof RecurringOrder) {
			$apiData['L_BILLINGTYPE0'] = 'RecurringPayments';
			$apiData['L_BILLINGAGREEMENTDESCRIPTION0'] = $order->getBillingDescription();
		}

		$apiData['PAYMENTREQUEST_0_CURRENCYCODE'] = $order->getCurrency()->getIso4217Code();
		$apiData['PAYMENTREQUEST_0_ITEMAMT'] = $order->getTotalForProductItems();
		$apiData['PAYMENTREQUEST_0_AMT'] = $order->getTotal();

		$apiData['PAYMENTREQUEST_0_PAYMENTACTION'] = 'Sale';

		return $apiData;
	}

	/**
	 * @param Order $order
	 * @return array
	 */
	public function makeSetExpressCheckoutCall(Order $order)
	{
		$apiData = $this->getSetExpressCheckoutApiData($order);

		$result = $this->callPaypalApi($apiData);

		return $result;
	}


	/**
	 * @param $returnUrl
	 * @return array
	 */
	public function makeRequestPermissionsCall($returnUrl)
	{
		$apiData = $this->getRequestPermissionsApiData($returnUrl);

		$result = $this->callPaypalApi($apiData);

		return $result;
	}


	/**
	 * @param string $returnUrl
	 * @return string
	 */
	public function getGrantPermissionsUrl($returnUrl)
	{
		$requestPermissionsCallResult = $this->makeRequestPermissionsCall($returnUrl);

		$queryData = array(
			'cmd' => '_grant-permission',
			'request_token' => $requestPermissionsCallResult['TOKEN']
		);

		$url = $this->getPaypalRedirectUrl($queryData);

		return $url;
	}

	/**
	 * @param $returnUrl
	 * @return array
	 */
	protected function getRequestPermissionsApiData($returnUrl)
	{
		$url = $this->getPaypalApiUrl2() . '/Permissions/RequestPermissions';

		$apiData = $this->getBaseApiData($url);

		unset($apiData['USER']);
		unset($apiData['PWD']);
		unset($apiData['SIGNATURE']);

		$apiData['___URL'] = $url;

		$apiData['___HEADERS']['X-PAYPAL-SECURITY-USERID'] = $this->apiUsername;
		$apiData['___HEADERS']['X-PAYPAL-SECURITY-PASSWORD'] = $this->apiPassword;
		$apiData['___HEADERS']['X-PAYPAL-SECURITY-SIGNATURE'] = $this->apiSignature;
		$apiData['___HEADERS']['X-PAYPAL-REQUEST-DATA-FORMAT'] = 'NV';
		$apiData['___HEADERS']['X-PAYPAL-RESPONSE-DATA-FORMAT'] = 'NV';
		$apiData['___HEADERS']['X-PAYPAL-APPLICATION-ID'] = $this->applicationId;

		$apiData['requestEnvelope.errorLanguage'] = 'en_US';
		$apiData['scope(0)'] = 'EXPRESS_CHECKOUT';
		$apiData['scope(1)'] = 'ACCESS_BASIC_PERSONAL_DATA';
		$apiData['scope(2)'] = 'REFUND';
		$apiData['scope(3)'] = 'DIRECT_PAYMENT';
		$apiData['callback'] = $returnUrl;

		return $apiData;
	}

	/**
	 * @return array
	 */
	protected function getGetBasicPersonalDataApiData()
	{
		$url = $this->getPaypalApiUrl2() . '/Permissions/GetBasicPersonalData';

		$apiData = $this->getBaseApiData($url);

		unset($apiData['USER']);
		unset($apiData['PWD']);
		unset($apiData['SIGNATURE']);

		$apiData['___URL'] = $url;

		$apiData['___HEADERS']['X-PAYPAL-REQUEST-DATA-FORMAT'] = 'NV';
		$apiData['___HEADERS']['X-PAYPAL-RESPONSE-DATA-FORMAT'] = 'NV';
		$apiData['___HEADERS']['X-PAYPAL-APPLICATION-ID'] = $this->applicationId;

		$apiData['requestEnvelope.errorLanguage'] = 'en_US';
		$apiData['attributeList.attribute(0)'] = 'http://axschema.org/contact/email';
		$apiData['attributeList.attribute(1)'] = 'http://schema.openid.net/contact/fullname';

		return $apiData;
	}


	/**
	 *
	 */
	protected function makeGetBasicPersonalDataCall()
	{
		$apiData = $this->getGetBasicPersonalDataApiData();

		$result = $this->callPaypalApi($apiData);

		return $result;
	}


	/**
	 * @return array
	 */
	public function getGetBasicPersonalData()
	{
		$z = $this->makeGetBasicPersonalDataCall();

		return $z;
	}

	/**
	 *
	 */
	public function makeGetAccessTokenCall($requestToken, $verificationCode)
	{
		$apiData = $this->getGetAccessTokenApiData($requestToken, $verificationCode);

		$result = $this->callPaypalApi($apiData);

		return $result;
	}

	/**
	 *
	 */
	protected function getGetAccessTokenApiData($requestToken, $verificationCode)
	{
		$apiData = $this->getBaseApiData();

		unset($apiData['USER']);
		unset($apiData['PWD']);
		unset($apiData['SIGNATURE']);
		unset($apiData['VERSION']);

		$apiData['___URL'] = $this->getPaypalApiUrl2() . '/Permissions/GetAccessToken';

		$apiData['___HEADERS']['X-PAYPAL-SECURITY-USERID'] = $this->apiUsername;
		$apiData['___HEADERS']['X-PAYPAL-SECURITY-PASSWORD'] = $this->apiPassword;
		$apiData['___HEADERS']['X-PAYPAL-SECURITY-SIGNATURE'] = $this->apiSignature;
		$apiData['___HEADERS']['X-PAYPAL-REQUEST-DATA-FORMAT'] = 'NV';
		$apiData['___HEADERS']['X-PAYPAL-RESPONSE-DATA-FORMAT'] = 'NV';
		$apiData['___HEADERS']['X-PAYPAL-APPLICATION-ID'] = $this->applicationId;

		$apiData['requestEnvelope.errorLanguage'] = 'en_US';
		$apiData['token'] = $requestToken;
		$apiData['verifier'] = $verificationCode;

		return $apiData;
	}

	/**
	 * @param string $requestToken
	 * @param string $verificationCode
	 * @return array
	 */
	public function getRequestedPermissionsAccessTokenAndSecret($requestToken, $verificationCode)
	{
		$result = $this->makeGetAccessTokenCall($requestToken, $verificationCode);

		return array($result['TOKEN'], $result['TOKENSECRET']);
	}

	/**
	 * @param string $token
	 * @return array
	 */
	protected function getGetExpressCheckoutDetailsApiData($token)
	{
		$apiData = $this->getBaseApiData();

		$apiData['METHOD'] = 'GetExpressCheckoutDetails';

		$apiData['TOKEN'] = $token;

		return $apiData;
	}

	/**
	 * @param string $token
	 * @return array
	 */
	public function makeGetExpressCheckoutDetailsCall($token)
	{
		$apiData = $this->getGetExpressCheckoutDetailsApiData($token);

		$result = $this->callPaypalApi($apiData);

		return $result;
	}

	/**
	 * @param Order $order
	 * @param array $checkoutDetails
	 * @return array
	 */
	protected function getDoExpressCheckoutPaymentApiData(Order $order, $checkoutDetails)
	{
		$apiData = $this->getBaseApiData();

		$apiData['METHOD'] = 'DoExpressCheckoutPayment';

		$apiData['TOKEN'] = $checkoutDetails['TOKEN'];
		$apiData['PAYERID'] = $checkoutDetails['PAYERID'];
		$apiData['PAYMENTREQUEST_0_AMT'] = $checkoutDetails['PAYMENTREQUEST_0_AMT'];
		$apiData['PAYMENTREQUEST_0_CURRENCYCODE'] = $checkoutDetails['PAYMENTREQUEST_0_CURRENCYCODE'];
		$apiData['PAYMENTREQUEST_0_NOTIFYURL'] = $this->getNotificationUrl();

		return $apiData;
	}

	/**
	 * @param Order $order
	 * @param array $checkoutDetails
	 * @return array
	 */
	public function makeDoExpressCheckoutPaymentCall(Order $order, $checkoutDetails)
	{
		$apiData = $this->getDoExpressCheckoutPaymentApiData($order, $checkoutDetails);

		$result = $this->callPaypalApi($apiData);

		return $result;
	}


	/**
	 * @param RecurringOrder $recurringOrder
	 * @param $checkoutDetails
	 * @return array
	 * @throws Exception\RuntimeException
	 */
	public function getCreateRecurringPaymentsProfileApiData(RecurringOrder $recurringOrder, $checkoutDetails)
	{
		$apiData = $this->getBaseApiData();

		$now = new \DateTime();

		$apiData['METHOD'] = 'CreateRecurringPaymentsProfile';
		$apiData['TOKEN'] = $checkoutDetails['TOKEN'];
		$apiData['PROFILESTARTDATE'] = $now->format('c');
		$apiData['DESC'] = $recurringOrder->getBillingDescription();

		$periodDimension = $recurringOrder->getPeriodDimension();
		$billingPeriod = null;
		switch ($periodDimension) {
			case RecurringOrderPeriodDimension::DAY:
			{
				$billingPeriod = 'Day';
				break;
			}
			case RecurringOrderPeriodDimension::MONTH:
			{
				$billingPeriod = 'Month';
				break;
			}
			case RecurringOrderPeriodDimension::WEEK:
			{
				$billingPeriod = 'Week';
				break;
			}
			default:
				{
				throw new Exception\RuntimeException('Do not know how to convert period dimension "' . $periodDimension . '" to Paypal billing period value.');
				}
		}

		$apiData['NOTIFYURL'] = $this->getNotificationUrl() . '/' . self::CUSTOMER_RETURN_SUFFIX_RECURRING;

		$apiData['BILLINGPERIOD'] = $billingPeriod;
		$apiData['BILLINGFREQUENCY'] = $recurringOrder->getPeriodLength();
		$apiData['AMT'] = $recurringOrder->getTotal();
		$apiData['CURRENCYCODE'] = $recurringOrder->getCurrency()->getIso4217Code();
		$apiData['EMAIL'] = $checkoutDetails['EMAIL'];

		$items = $recurringOrder->getItems();

		$counter = 0;

		foreach ($items as $item) {

			$itemData = array();

			if ($item instanceof RecurringPaymentProductItem) {
				$counter = intval($counter);

				$itemData = array(
					'L_PAYMENTREQUEST_0_ITEMCATEGORY' . $counter => 'Digital',
					'L_PAYMENTREQUEST_0_AMT' . $counter => $item->getPrice() / $item->getQuantity(),
					'L_PAYMENTREQUEST_0_NAME' . $counter => $item->getDescription(),
					'L_PAYMENTREQUEST_0_QTY' . $counter => $item->getQuantity()
				);
			} else if ($item instanceof RecurringPaymentPaymentProviderItem) {
				//$apiData['HANDLINGAMT'] = $item->getPrice();
			}

			$counter++;

			$apiData = $apiData + $itemData;
		}

		return $apiData;
	}

	/**
	 * @param RecurringOrder $recurringPayment
	 * @param $checkoutDetails
	 * @return array
	 */
	public function makeCreateRecurringPaymentsProfileCall(RecurringOrder $recurringPayment, $checkoutDetails)
	{
		$apiData = $this->getCreateRecurringPaymentsProfileApiData($recurringPayment, $checkoutDetails);

		$result = $this->callPaypalApi($apiData);

		return $result;
	}

	/**
	 * @param array $apiData
	 * @return array
	 */
	protected function callPaypalApi($apiData)
	{
		$apiUrl = $this->getPaypalApiUrl();

		if (isset($apiData['___URL'])) {
			$apiUrl = $apiData['___URL'];
			unset($apiData['___URL']);
		}

		\Log::debug('callPaypalApi URL: ', $apiUrl);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $apiUrl);

		if (isset($apiData['___HEADERS'])) {
			$headers = $apiData['___HEADERS'];
			unset($apiData['___HEADERS']);
			foreach ($headers as $name => &$value) {
				$value = $name . ': ' . $value;
			}

			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

			\Log::debug('callPaypalApi HEADERS: ', $headers);
		}

		\Log::debug('callPaypalApi POST: ', $apiData);

		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($apiData));
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_USERAGENT, 'cURL/PHP');

		$rawResponse = curl_exec($ch);

		\Log::debug('callPaypalApi RAW RESPONSE: ', $rawResponse);

		$response = $this->decodePaypalResponse($rawResponse);

		\Log::debug('callPaypalApi RESPONSE: ', $response);

		return $response;
	}

	/**
	 * @param string $response
	 * @return array
	 */
	protected function decodePaypalResponse($response)
	{
		$result = array();

		foreach (explode('&', $response) as $z1) {

			$key = '';
			$value = '';
			list($key, $value) = explode('=', $z1);

			$result[strtoupper(urldecode($key))] = urldecode($value);
		};

		return $result;
	}

	/**
	 * @param array|null $queryData
	 * @return string
	 */
	public function getReturnUrl($queryData = null)
	{
		$url = $this->returnHost . $this->getBaseUrl();

		if ($queryData) {
			$url = $url . '?' . http_build_query($queryData);
		}

		return $url;
	}


	/**
	 * @param ShopOrder $order
	 */
	public function updateShopOrder(ShopOrder $order)
	{
		$paymentProviderOrderItem = $order->getOrderItemByPayementProvider();

		if ($paymentProviderOrderItem->getPaymentProviderId() != $this->getId()) {

			$order->removeOrderItem($paymentProviderOrderItem);

			$paymentProviderOrderItem = $order->getOrderItemByPayementProvider($this->getId());
		}

		$paymentProviderOrderItem->setPrice($order->getTotalForProductItems() * 0.10);
	}

	/**
	 * @param ShopOrder $order
	 * @return bool
	 * @throws Exception\RuntimeException
	 */
	public function validateShopOrder(ShopOrder $order)
	{
		if ($order->getTotalForProductItems() < 10.00) {
			throw new Exception\RuntimeException('Total is too small!!!');
		}

		return true;
	}

	/**
	 * @param ShopOrder $order
	 * @param ResponseInterface $response
	 */
	public function processShopOrder(ShopOrder $order, ResponseInterface $response)
	{
		parent::processShopOrder($order, $response);

		// This is Paypal specific behaviour.
		$proxyActionUrlQueryData = array(
			self::REQUEST_KEY_SHOP_ORDER_ID => $order->getId()
		);

		$this->redirectToProxy($proxyActionUrlQueryData, $response);
	}

	/**
	 * @param RecurringOrder $order
	 * @param ResponseInterface $response
	 */
	public function processRecurringOrder(RecurringOrder $order, ResponseInterface $response)
	{
		parent::processRecurringOrder($order, $response);

		// This is Paypal specific behaviour.
		$proxyActionUrlQueryData = array(
			self::REQUEST_KEY_RECURRING_ORDER_ID => $order->getId()
		);

		$this->redirectToProxy($proxyActionUrlQueryData, $response);
	}

	/**
	 * @param Order $order
	 * @param LocaleInterface $locale
	 * @return boolean
	 */
	public function getOrderItemDescription(Order $order, LocaleInterface $locale = null)
	{
		return 'Paypal fee - ' . ($order->getTotalForProductItems() * 0.10) . ' ' . $order->getCurrency()->getIso4217Code();
	}

	/**
	 * @param array $ipnData
	 * @return string
	 */
	public function validateIpn($ipnData)
	{
		$queryData = array(
			'cmd' => '_notify-validate'
		);

		$validationUrl = $this->paypalRedirectUrl . '?' . http_build_query($queryData);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $validationUrl);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($ipnData));
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_USERAGENT, 'cURL/PHP');
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

		$rawResponse = curl_exec($ch);

		return $rawResponse == 'VERIFIED';
	}

	/**
	 * @param $queryData
	 * @return string
	 */
	public function getCustomerReturnActionUrl($queryData)
	{
		$query = http_build_query($queryData);

		return $this->getBaseUrl() . '/' . self::CUSTOMER_RETURN_URL_POSTFIX . '?' . $query;
	}

	/**
	 * @param $queryData
	 * @return string
	 */
	public function getProviderNotificationActionUrl($queryData)
	{
		$query = http_build_query($queryData);

		return $this->getBaseUrl() . '/' . self::PROVIDER_NOTIFICATION_URL_POSTFIX . '?' . $query;
	}

	/**
	 * @param string $accessTokenSecret
	 */
	public function setAccessTokenSecret($accessTokenSecret)
	{
		$this->accessTokenSecret = $accessTokenSecret;
	}

	/**
	 * @param string $accessToken
	 */
	public function setAccessToken($accessToken)
	{
		$this->accessToken = $accessToken;
	}

	/**
	 * @param $urlForAuthString
	 * @return string
	 */
	protected function getXPaypalAuthorizationHeaderValue($urlForAuthString)
	{
		return AuthSignature::generateFullAuthString(
			$this->apiUsername,
			$this->apiPassword,
			$this->getAccessToken(),
			$this->getAccessTokenSecret(),
			'POST',
			$urlForAuthString
		);
	}

	/**
	 * @param $transactionId
	 * @param null $amount
	 * @param Currency $currency
	 * @param string $note
	 * @param null $invoiceId
	 * @return array
	 */
	protected function getRefundTransactionApiData(
		$transactionId,
		$amount = null,
		Currency $currency = null,
		$note = '',
		$invoiceId = null
	)
	{
		$apiData = $this->getBaseApiData();

		$apiData['METHOD'] = 'RefundTransaction';
		$apiData['TRANSACTIONID'] = $transactionId;

		$apiData['REFUNDTYPE'] = $amount == null ? 'Full' : 'Partial';

		if ($amount != null) {
			$apiData['AMT'] = $amount;
			$apiData['CURRENCYCODE'] = $currency->getIso4217Code();
		}

		$apiData['NOTE'] = $note;
		if (!empty($invoiceId)) {
			$apiData['INVOICEID'] = $invoiceId;
		}

		return $apiData;
	}

	/**
	 * @param Order $order
	 * @param string $note
	 * @return array
	 * @throws \RuntimeException
	 */
	public function makeRefundTransactionCall(Order $order, $note = '', $amount = null)
	{
		if ($order instanceof ShopOrder) {

			if ($order->getTransaction()->getStatus() == TransactionStatus::SUCCESS) {

				$apiData = $this->getRefundTransactionApiData(
					$order->getTransaction()->getParameterValue(self::PHASE_NAME_DO_PAYMENT, 'PAYMENTINFO_0_TRANSACTIONID'),
					$amount,
					$order->getCurrency(),
					$note,
					$order->getId()
				);

				$result = $this->callPaypalApi($apiData);

				$order->getTransaction()->addToParameters(self::PHASE_NAME_REFUND_TRANSACTION, $result);

				if ($result['ACK'] == 'Success') {
					$order->getTransaction()->setStatus(TransactionStatus::REFUNDED);
				}

				$this->getOrderProvider()->store($order);
			} else {

				throw new \RuntimeException('Only successful transactions can be refunded.');
			}
		} else {
			throw new \RuntimeException(sprintf('Do not know how to refund "%s" orders yet.', get_class($order)));
		}

		return $result;
	}
}
