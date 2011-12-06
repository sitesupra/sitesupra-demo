<?php

namespace Project\Payment\DummyShop;

use Supra\Controller\Pages\BlockController;
use Supra\Payment\Provider\PaymentProviderCollection;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Payment\Entity\Order\OrderItem;
use Supra\Payment\Entity\Order\OrderProductItem;
use Supra\Payment\Entity\Order\OrderPaymentProviderItem;
use Supra\Payment\Entity\Order\Order;
use Supra\Payment\Order\OrderProvider;
use Supra\Payment\Order\Exception\RuntimeException as OrderRuntimeException;
use Supra\Payment\Currency\CurrencyProvider;
use Supra\Controller\Pages\Request\PageRequestEdit;
use Supra\Controller\Pages\Request\PageRequestView;
use Project\Payment\Paypal\OrderPaypalItem;
use Supra\Payment\Order\OrderStatus;

class DummyShopController extends BlockController
{
	const ACTION_TYPE_VALIDATE_ORDER = 'validate';
	const ACTION_TYPE_UPDATE_ORDER = 'update';
	const ACTION_TYPE_RETURN = 'return';

	const URL_KEY_ORDER_ID = 'o';

	const ACTION_KEY = 'shopAction';

	const TWIG_INDEX = 'index.html.twig';

	/**
	 * @var Order
	 */
	protected $order;

	/**
	 * @var OrderProvider
	 */
	protected $orderProvider;

	public function __construct()
	{
		parent::__construct();

		$this->orderProvider = new OrderProvider();
	}

	/**
	 * Returns URL for validation action.
	 * @return string
	 */
	private function getValidateOrderUrl()
	{
		/* @var $request PageRequestView */
		$request = $this->getRequest();

		$url = $request->getRequestUri();

		$queryParameters = array(self::ACTION_KEY => self::ACTION_TYPE_VALIDATE_ORDER);

		return $url . http_build_query($queryParameters);
	}

	/**
	 * Returns URL for return redirect.
	 * @return string
	 */
	private function getReturnUrl()
	{
		/* @var $request PageRequestView */
		$request = $this->getRequest();

		$scriptUri = $request->getServerValue('SCRIPT_URI');
		$scriptUrl = $request->getServerValue('SCRIPT_URL');
		
		$serverHostWithProtocol = substr($scriptUri, 0, -strlen($scriptUrl));
		
		$url = $serverHostWithProtocol . $request->getRequestUri();

		$queryParameters = array(
				self::ACTION_KEY => self::ACTION_TYPE_RETURN
		);

		return $url . '?' . http_build_query($queryParameters);
	}

	/**
	 * Returns URL to for order update action.
	 * @return string
	 */
	private function getUpdateOrderUrl()
	{
		$request = $this->getRequest();

		$url = $request->getRequestUri();

		$queryParameters = array(self::ACTION_KEY => self::ACTION_TYPE_UPDATE_ORDER);

		return $url . http_build_query($queryParameters);
	}

	/**
	 * Fetches/creates and sets this order to open order for current user.
	 */
	public function getOpenOrderForCurrentUser()
	{
		$user = $this->getUser();
		
		$this->order = $this->orderProvider->getOpenOrderForUser($user);
		
		$lm = ObjectRepository::getLocaleManager($this);
		$currentLocale = $lm->getCurrent();
		
		$this->order->updateLocale($currentLocale);
		
		$this->order->setReturnUrl($this->getReturnUrl());
	}

	/**
	 * Fetches and sets this order from order id retreived from request.
	 */
	public function getOrderFromRequest()
	{
		$request = $this->getRequest();

		$orderId = $request->getParameter(self::URL_KEY_ORDER_ID);

		$this->order = $this->orderProvider->getOrder($orderId);
	}

	public function execute()
	{
		$request = $this->getRequest();

		if ($request instanceof PageRequestView) {

			$action = $request->getParameter(self::ACTION_KEY);

			switch ($action) {

				case self::ACTION_TYPE_UPDATE_ORDER: {

						$this->getOpenOrderForCurrentUser();
						$this->updateOrder();
					} break;

				case self::ACTION_TYPE_VALIDATE_ORDER: {

						$this->getOpenOrderForCurrentUser();
						$this->validateOrder();
					} break;

				case self::ACTION_TYPE_RETURN: {

						$this->getOrderFromRequest();
						$this->handleReturn();
					} break;

				default: {

						$this->getOpenOrderForCurrentUser();
						$this->showOrder();
					}
			}
		}
		else {

			$this->showOrderForCms();
		}
	}

	/**
	 * Loads property definition array.
	 * @return array
	 */
	public function getPropertyDefinition()
	{
		return array();
	}

	/**
	 * Dummy current user source, always returns "admin" user.
	 * @return User
	 */
	protected function getUser()
	{
		$up = ObjectRepository::getUserProvider('#cms');

		return $up->findUserByLogin('admin');
	}

	/**
	 * Dummy payment provider source, allways returns last payment provider configured and added to collection.
	 * @return type 
	 */
	private function getPaymentProvider()
	{
		$paymentProviderCollection = ObjectRepository::getPaymentProviderCollection($this);
		$providerIds = $paymentProviderCollection->getIds();

		$firstPaymentProviderId = array_pop($providerIds);
		$paymentProvider = $paymentProviderCollection->get($firstPaymentProviderId);

		return $paymentProvider;
	}

	/**
	 * Dummy order update - only adjusts amount of products.
	 */
	private function updateOrder()
	{
		$request = $this->getRequest();

		$itemAmount = $request->getParameter('amount');

		$productIds = array(111, 222, 333);

		$currencyProvider = new CurrencyProvider();

		$currency = $currencyProvider->getCurrencyByIsoCode('USD');
		$this->order->setCurrency($currency);

		foreach ($productIds as $productId) {

			$product = new DummyProduct($productId);

			$orderItem = $this->order->getOrderItemByProduct($product);

			$orderItem->setQuantity($itemAmount);
			$orderItem->setPriceFromProduct($currency);
		}

		$paymentProvider = $this->getPaymentProvider();

		$paymentProvider->updateOrder($this->order);

		$this->orderProvider->store($this->order);

		$this->showOrder();
	}

	/**
	 * Validates order via payment provider and redirects to 
	 * proxy action of payment provider to continue with payment.
	 */
	public function validateOrder()
	{
		$response = $this->getResponse();

		$paymentProvider = $this->getPaymentProvider();

		if ($paymentProvider->validateOrder($this->order)) {

			$paymentProvider->prepareTransaction($this->order);

			$paymentProvider->redirectToProxy($this->order, $response);
		}
		else {

			$this->showOrder();
		}
	}

	/**
	 * Assigns "#" to various action variables and renders shop block  
	 * for CMS.
	 */
	public function showOrderForCms()
	{
		$response = $this->getResponse();

		$response->assign('valiateOrderUrl', '#');
		$response->assign('updateOrderUrl', '#');
		$response->outputTemplate(self::TWIG_INDEX);
	}

	/**
	 * Assigns order data and various URLs to response and renders shop 
	 * block for frontend.
	 */
	public function showOrder()
	{
		$response = $this->getResponse();

		$validateOrderUrl = $this->getValidateOrderUrl();
		$updateOrderUrl = $this->getUpdateOrderUrl();

		$orderItems = $this->order->getItems();

		$response->assign('orderItems', $orderItems);
		$response->assign('orderId', $this->order->getId());
		$response->assign('validateOrderUrl', $validateOrderUrl);

		$response->assign('updateOrderUrl', $updateOrderUrl);

		$response->outputTemplate(self::TWIG_INDEX);
	}

	/**
	 * Handles return from payment provider.
	 */
	public function handleReturn()
	{
		$orderStatus = $this->order->getStatus();

		switch ($orderStatus) {

			case OrderStatus::PAYMENT_RECEIVED: {
					
				} break;

			case OrderStatus::PAYMENT_CANCELED: {
					
				} break;

			case OrderStatus::PAYMENT_FAILED: {
					
				} break;

			case OrderStatus::SYSTEM_ERROR:
			default: {
					
				}
		}
	}

}
