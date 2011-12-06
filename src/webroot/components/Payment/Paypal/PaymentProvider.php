<?php

namespace Project\Payment\Paypal;

use Supra\Payment\Provider\PaymentProviderAbstraction;
use Supra\Payment\Entity\Order\OrderPaymentProviderItem;
use Supra\Payment\Entity\Order\Order;
use Supra\Payment\Entity\Order\OrderItem;
use Supra\Payment\Order\OrderStatus;
use Supra\Payment\Entity\Order\OrderProductItem;
use Supra\Locale\Locale;
use Supra\ObjectRepository\ObjectRepository;

class PaymentProvider extends PaymentProviderAbstraction
{
	const REQUEST_KEY_TOKEN = 'TOKEN';
	const TRANSACTION_PARAMETER_NAME_TOKEN = 'TOKEN';
	const TRANSCACTION_PARAMETER_TRANSACTIONID = 'PAYMENTINFO_0_TRANSACTIONID';

	const PHASE_NAME_PROXY = 'proxy';
	const PHASE_NAME_CHECKOUT_DETAILS = 'checkoutDetails';
	const PHASE_NAME_DO_PAYMENT= 'doPayment';
	const PHASE_NAME_IPN = 'ipn-';

	const CUSTOMER_RETURN_ACTION = 'return';
	const CUSTOMER_RETURN_ACTION_RETURN = 'return';
	const CUSTOMER_RETURN_ACTION_CANCEL = 'cancel';

	const EVENT_PAYER_CHECKOUT_DETAILS = 'paypalPayerCheckoutDetails';

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
	public function getPaypalRedirectUrl()
	{

		return $this->paypalRedirectUrl;
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

		$apiData['RETURNURL'] = $this->returnHost . $this->getBaseUrl() . '/' . self::CUSTOMER_RETURN_ACTION . '/' . self::CUSTOMER_RETURN_ACTION_RETURN;
		$apiData['CANCELURL'] = $this->returnHost . $this->getBaseUrl() . '/' . self::CUSTOMER_RETURN_ACTION . '/' . self::CUSTOMER_RETURN_ACTION_CANCEL;
		$apiData['NOTIFYURL'] = $this->getNotificationUrl();

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
						'L_AMT' . $counter => $orderItem->getPrice() / $orderItem->getQuantity(),
						'L_DESC' . $counter => $orderItem->getDescription(),
						'L_QTY' . $counter => $orderItem->getQuantity()
				);

				$totalItemAmount += $orderItem->getPrice();

				$totalAmount += $orderItem->getPrice();

				$totalItemQuantity += $orderItem->getQuantity();
			}
			else if ($orderItem instanceof OrderPaymentProviderItem) {

				$apiData['HANDLINGAMT'] = $orderItem->getPrice();
			}

			$counter ++;

			$apiData = $apiData + $itemData;
		}

		$apiData['CURRENCYCODE'] = $order->getCurrency()->getIsoCode();
		$apiData['ITEMAMT'] = $order->getTotalForProductItems();
		$apiData['AMT'] = $order->getTotal();

		$apiData['PAYMENTACTION'] = 'Sale';

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
	public function updateOrder(Order $order)
	{
		$paymentProviderOrderItem = $order->getOrderItemByPayementProvider();

		if ($paymentProviderOrderItem->getPaymentProviderId() != $this->getId()) {

			$order->removeOrderItem($paymentProviderOrderItem);

			$paymentProviderOrderItem = $order->getOrderItemByPayementProvider($this->getId());
		}

		$paymentProviderOrderItem->setPrice($order->getTotalForProductItems() * 0.10);
	}

	/**
	 * @param Order $order 
	 * @return boolean
	 */
	public function validateOrder(Order $order)
	{
		if ($order->getTotalForProductItems() < 10.00) {
			throw new Exception\RuntimeException('Total is too small!!!');
		}

		return true;
	}

	/**
	 * @param Order $order
	 * @param Locale $locale 
	 * @return boolean
	 */
	public function getOrderItemDescription(Order $order, Locale $locale = null)
	{
		return 'Paypal fee - ' . ($order->getTotalForProductItems() * 0.10) . ' ' . $order->getCurrency()->getIsoCode();
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

}
