<?php

namespace Project\Payment\Paypal;

use Supra\Payment\Provider\PaymentProviderAbstraction;
use Supra\Payment\Entity\Order\OrderPaymentProviderItem;
use Supra\Payment\Entity\Order\Order;
use Supra\Payment\Entity\Order\ShopOrder;
use Supra\Payment\Entity\Order\RecurringOrder;
use Supra\Payment\Entity\Order\OrderItem;
use Supra\Payment\Order\OrderStatus;
use Supra\Payment\Entity\Order\OrderProductItem;
use Supra\Locale\Locale;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Payment\Entity\Transaction\Transaction;
use Supra\Payment\Transaction\TransactionType;
use Supra\Payment\RecurringPayment\RecurringPaymentStatus;
use Supra\Payment\Entity\Order\RecurringPayment;
use Supra\Payment\Entity\RecurringPayment\RecurringPaymentProductItem;
use Supra\Payment\Entity\RecurringPayment\RecurringPaymentPaymentProviderItem;
use Supra\Response\ResponseInterface;
use Supra\Payment\Order\RecurringOrderPeriodDimension;

class PaymentProvider extends PaymentProviderAbstraction
{
	const REQUEST_KEY_TOKEN = 'TOKEN';
	const TRANSACTION_PARAMETER_NAME_TOKEN = 'TOKEN';
	const TRANSACTION_PARAMETER_TRANSACTIONID = 'PAYMENTINFO_0_TRANSACTIONID';
	const TRANSACTION_PARAMETER_PROFILEID = 'PROFILEID';

	const PHASE_NAME_PAYPAL_SET_EXPRESS_CHECKOUT = 'paypal-SetExpressCheckout';
	const PHASE_NAME_CHECKOUT_DETAILS = 'paypal-CheckoutDetails';
	const PHASE_NAME_DO_PAYMENT= 'paypal-DoPayment';
	const PHASE_NAME_CREATE_RECURRING_PAYMENT = 'paypal-CreateRecurringPaymentsProfile';
	const PHASE_NAME_IPN = 'paypal-ipn-';

	const CUSTOMER_RETURN_ACTION = 'return';
	const CUSTOMER_RETURN_ACTION_RETURN = 'return';
	const CUSTOMER_RETURN_ACTION_CANCEL = 'cancel';
	const CUSTOMER_RETURN_SUFFIX_SHOP = 'shop';
	const CUSTOMER_RETURN_SUFFIX_RECURRING = 'recurring';

	const EVENT_PAYER_CHECKOUT_DETAILS = 'paypalPayerCheckoutDetails';

	const REQUEST_KEY_SHOP_ORDER_ID = 'shopo';
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
	protected $paypalApiUrl;

	/**
	 * @var string
	 */
	protected $paypalRedirectUrl;
	protected $returnHost;
	protected $callbackHost;

	/**
	 * @param string $apiUsername 
	 */
	public function setApiUsername($apiUsername)
	{
		$this->apiUsername = $apiUsername;
	}

	/**
	 * @param string $apiUsername 
	 */
	public function setApiPassword($apiPassword)
	{
		$this->apiPassword = $apiPassword;
	}

	/**
	 * @param string $apiUsername 
	 */
	public function setApiSignature($apiSignature)
	{
		$this->apiSignature = $apiSignature;
	}

	/**
	 * @param string $paypalServiceUrl 
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
	 * @return array
	 */
	protected function getBaseApiData()
	{
		$apiData = array();
		$apiData['VERSION'] = '82.0';

		$apiData['USER'] = $this->apiUsername;
		$apiData['PWD'] = $this->apiPassword;
		$apiData['SIGNATURE'] = $this->apiSignature;

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
	 */
	protected function getSetExpressCheckoutApiData(Order $order)
	{
		$apiData = $this->getBaseApiData();

		$apiData['METHOD'] = 'SetExpressCheckout';

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

		$orderItems = $order->getItems();

		$counter = 0;
		$totalItemQuantity = 0;
		$totalItemAmount = 0;
		$totalAmount = 0;

		foreach ($orderItems as $orderItem) {
			/* @var $orderItem OrderItem */

			$itemData = array();

			if ($orderItem instanceof OrderProductItem) {

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
			}

			$counter ++;

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
	 * @param type $token
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
	 * @param type $token
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
	 * @param type $checkoutDetails
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
	 * @param type $checkoutDetails
	 * @return array
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
			case RecurringOrderPeriodDimension::DAY: {
					$billingPeriod = 'Day';
					break;
				}
			case RecurringOrderPeriodDimension::MONTH: {
					$billingPeriod = 'Month';
					break;
				}
			case RecurringOrderPeriodDimension::WEEK: {
					$billingPeriod = 'Week';
					break;
				}
			default: {
					throw new Paypal\Exception\RuntimeException('Do not know how to convert period dimension "' . $periodDimension . '" to Paypal billing period value.');
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

			$counter ++;

			$apiData = $apiData + $itemData;
		}

		return $apiData;
	}

	/**
	 * @param RecurringPayment $recurringPayment
	 * @param array $checkoutDetails
	 * @return array 
	 */
	public function makeCreateRecurringPaymentsProfileCall(RecurringOrder $recurringPayment, $checkoutDetails)
	{
		$apiData = $this->getCreateRecurringPaymentsProfileApiData($recurringPayment, $checkoutDetails);

		$result = $this->callPaypalApi($apiData);

		return $result;
	}

	/**
	 * @param type $apiData
	 * @return array
	 */
	protected function callPaypalApi($apiData)
	{
		$apiUrl = $this->getPaypalApiUrl();

		\Log::debug('callPaypalApi POST: ', $apiData);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $apiUrl);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($apiData));
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_USERAGENT, 'cURL/PHP');

		$rawResponse = curl_exec($ch);

		$response = $this->decodePaypalResponse($rawResponse);

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
	 * @return string
	 */
	public function getReturnUrl()
	{
		return $this->returnHost . $this->getBaseUrl();
	}

	/**
	 * @param Order $order 
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
	 * @return boolean
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
	 * @param Locale $locale 
	 * @return boolean
	 */
	public function getOrderItemDescription(Order $order, Locale $locale = null)
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
	 * @return string
	 */
	public function getCustomerReturnActionUrl($queryData)
	{
		$query = http_build_query($queryData);

		return $this->getBaseUrl() . '/' . self::CUSTOMER_RETURN_URL_POSTFIX . '?' . $query;
	}

	/**
	 * @return string
	 */
	public function getProviderNotificationActionUrl($queryData)
	{
		$query = http_build_query($queryData);

		return $this->getBaseUrl() . '/' . self::PROVIDER_NOTIFICATION_URL_POSTFIX . '?' . $query;
	}

}
