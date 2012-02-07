<?php

namespace Project\Payment\DummyShop;

use Supra\Controller\Pages\BlockController;
use Supra\Controller\Pages\Request\PageRequestEdit;
use Supra\Controller\Pages\Request\PageRequestView;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Payment\Currency\CurrencyProvider;
use Supra\Payment\Provider\PaymentProviderCollection;
use Supra\Payment\Entity\Order\OrderItem;
use Supra\Payment\Entity\Order\OrderProductItem;
use Supra\Payment\Entity\Order\OrderPaymentProviderItem;
use Supra\Payment\Entity\Order\ShopOrder;
use Supra\Payment\Entity\Order\RecurringOrder;
use Supra\Payment\Order\OrderProvider;
use Supra\Payment\Order\RecurringOrderStatus;
use Supra\Payment\Order\Exception\RuntimeException as OrderRuntimeException;
use Supra\Payment\Action\CustomerReturnActionAbstraction;
use Supra\Payment\Order\RecurringOrderPeriodDimension;
use Doctrine\ORM\EntityManager;
use Supra\Payment\Entity\Transaction\Transaction;
use Supra\Payment\Transaction\TransactionStatus;

class DummyShopController extends BlockController
{
	const ACTION_TYPE_SUBMIT_ORDER = 'submit';
	const ACTION_TYPE_SUBMIT_RECURRING_ORDER = 'submitRecurring';
	const ACTION_TYPE_UPDATE_ORDER = 'update';
	const ACTION_TYPE_RETURN = 'return';
	const ACTION_TYPE_RETURN_RECURRING = 'returnRecurring';

	const ACTION_KEY = 'shopAction';

	const DEFAULT_SUBSCRIPTION = 'default-subscription';

	const TWIG_INDEX = 'index.html.twig';

	/**
	 * @var ShoOrder
	 */
	protected $order;

	/**
	 * @var RecurringOrder
	 */
	protected $recurringOrder;

	/**
	 * @var OrderProvider
	 */
	protected $orderProvider;

	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * @return EntityManager
	 */
	public function getEntityManager()
	{
		if (empty($this->em)) {
			$this->em = ObjectRepository::getEntityManager($this);
		}
		return $this->em;
	}

	/**
	 * @param EntityManager $em 
	 */
	public function setEntityManager(EntityManager $em)
	{
		$this->em = $em;
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
	 * Returns URL for order submit action.
	 * @return string
	 */
	private function getSubmitOrderUrl()
	{
		/* @var $request PageRequestView */
		$request = $this->getRequest();

		$url = '/' . $request->getActionString();

		$queryParameters = array(self::ACTION_KEY => self::ACTION_TYPE_SUBMIT_ORDER);

		return $url . '?' . http_build_query($queryParameters);
	}

	/**
	 * 
	 */
	private function getSubmitRecurringOrderUrl()
	{
		$request = $this->getRequest();

		$url = '/' . $request->getActionString();

		$queryParameters = array(self::ACTION_KEY => self::ACTION_TYPE_SUBMIT_RECURRING_ORDER);

		return $url . '?' . http_build_query($queryParameters);
	}

	/**
	 * Returns URL for return redirect.
	 * @return string
	 */
	private function getReturnToShopUrl()
	{
		/* @var $request PageRequestView */
		$request = $this->getRequest();

		// The next two lines are NOT identical! Do not remove!
		$scriptUri = $request->getServerValue('SCRIPT_URI');
		$scriptUrl = $request->getServerValue('SCRIPT_URL');

		$serverHostWithProtocol = substr($scriptUri, 0, -strlen($scriptUrl));

		$url = $serverHostWithProtocol . '/' . $request->getActionString();

		$queryParameters = array(
			self::ACTION_KEY => self::ACTION_TYPE_RETURN
		);

		return $url . '?' . http_build_query($queryParameters);
	}

	/**
	 * Returns URL for return redirect.
	 * @return string
	 */
	private function getReturnToShopUrlForRecurringOrder()
	{
		/* @var $request PageRequestView */
		$request = $this->getRequest();

		// The next two lines are NOT identical! Do not remove!
		$scriptUri = $request->getServerValue('SCRIPT_URI');
		$scriptUrl = $request->getServerValue('SCRIPT_URL');

		$serverHostWithProtocol = substr($scriptUri, 0, -strlen($scriptUrl));

		$url = $serverHostWithProtocol . '/' . $request->getActionString();

		$queryParameters = array(
			self::ACTION_KEY => self::ACTION_TYPE_RETURN_RECURRING
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

		$url = '/' . $request->getActionString();

		$queryParameters = array(self::ACTION_KEY => self::ACTION_TYPE_UPDATE_ORDER);

		return $url . '?' . http_build_query($queryParameters);
	}

	/**
	 * Fetches/creates and sets this order to open order for current user.
	 */
	protected function getOpenShopOrderForCurrentUser()
	{
		$user = $this->getUser();

		$orderProvider = $this->getOrderProvider();

		$this->order = $orderProvider->getOpenShopOrderForUser($user);

		$currentLocale = $this->getCurrentLocale();

		$this->order->updateLocale($currentLocale);

		$this->order->setReturnUrl($this->getReturnToShopUrl());
	}

	protected function getRecurringOrderForCurrentUser()
	{
		$user = $this->getUser();

		$orderProvider = $this->getOrderProvider();

		$order = $orderProvider->getRecurringOrderForUser($user);

		if (empty($order)) {

			$currentLocale = $this->getCurrentLocale();

			$currency = $this->getCurrencyByIsoCode('USD');

			$user = $this->getUser();

			$order = new RecurringOrder();

			$order->setUserId($user->getId());

			$order->setCurrency($currency);

			$order->updateLocale($currentLocale);
			$order->setReturnUrl($this->getReturnToShopUrlForRecurringOrder());

			$order->setPeriodLength(1);
			$order->setPeriodDimension(RecurringOrderPeriodDimension::MONTH);

			$product = new DummyProduct(999);

			$orderItem = $order->getOrderItemByProduct($product);

			$orderItem->setQuantity(1);
			$orderItem->setPriceFromProduct($currency);

			$order->setBillingDescription('Just some billing description');

			$orderProvider->store($order);
		}

		$this->recurringOrder = $order;
	}

	/**
	 * Fetches and sets this order from order id retreived from request.
	 */
	protected function getShopOrderFromRequest()
	{
		$request = $this->getRequest();

		$orderId = $request->getParameter(CustomerReturnActionAbstraction::QUERY_KEY_SHOP_ORDER_ID);

		$orderProvider = $this->getOrderProvider();

		$this->order = $orderProvider->getOrder($orderId);
	}

	public function execute()
	{
		$request = $this->getRequest();

		if ($request instanceof PageRequestView) {

			$action = $request->getParameter(self::ACTION_KEY);

			switch ($action) {

				case self::ACTION_TYPE_UPDATE_ORDER: {

						$this->getOpenShopOrderForCurrentUser();
						$this->updateOrder();
					} break;

				case self::ACTION_TYPE_SUBMIT_ORDER: {

						$this->getOpenShopOrderForCurrentUser();
						$this->submitOrder();
					} break;

				case self::ACTION_TYPE_SUBMIT_RECURRING_ORDER: {

						$this->getRecurringOrderForCurrentUser();
						$this->submitRecurringOrder();
					} break;

				case self::ACTION_TYPE_RETURN: {

						$this->getShopOrderFromRequest();
						$this->handleReturn();
					} break;

				default: {

						$this->getOpenShopOrderForCurrentUser();
						$this->showOrder();
					}
			}
		} else {

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

		return $up->findUserByLogin('admin@supra7.vig');
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

		$orderProvider = $this->getOrderProvider();

		$itemAmount = $request->getParameter('amount');

		$productIds = array(111, 222, 333);

		$currency = $this->getCurrencyByIsoCode('USD');
		$this->order->setCurrency($currency);

		foreach ($productIds as $productId) {

			$product = new DummyProduct($productId);

			$orderItem = $this->order->getOrderItemByProduct($product);

			$orderItem->setQuantity($itemAmount);
			$orderItem->setPriceFromProduct($currency);
		}

		$paymentProvider = $this->getPaymentProvider();

		$paymentProvider->updateShopOrder($this->order);

		$orderProvider->store($this->order);

		$this->showOrder();
	}

	/**
	 * Validates order and submits ot to processing via payment provider.
	 */
	public function submitOrder()
	{
		$response = $this->getResponse();

		$orderProvider = $this->getOrderProvider();

		$paymentProvider = $this->getPaymentProvider();

		if ($paymentProvider->validateShopOrder($this->order)) {

			$paymentProvider->processShopOrder($this->order, $response);

			$orderProvider->store($this->order);
		} else {

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

		$response->assign('submitOrderUrl', '#');
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

		$submitOrderUrl = $this->getSubmitOrderUrl();
		$submitRecurringOrderUrl = $this->getSubmitRecurringOrderUrl();
		$updateOrderUrl = $this->getUpdateOrderUrl();

		$orderItems = $this->order->getItems();

		$response->assign('orderItems', $orderItems);
		$response->assign('orderId', $this->order->getId());
		$response->assign('submitOrderUrl', $submitOrderUrl);
		$response->assign('submitRecurringOrderUrl', $submitRecurringOrderUrl);

		$response->assign('updateOrderUrl', $updateOrderUrl);

		$response->outputTemplate(self::TWIG_INDEX);
	}

	/**
	 * Handles return from payment provider.
	 */
	public function handleReturn()
	{
		/* @var $order ShopOrder */
		$order = $this->order;
		
		/* @var $transaction Transaction */
		$transaction = $order->getTransaction();

		$transactionStatus = $transaction->getStatus();

		switch ($transactionStatus) {

			case TransactionStatus::SUCCESS: {

					$this->getResponse()
							->output('<h1>THANKS FOR THE PAYMENT!!!</h1>');
				} break;

			case TransactionStatus::PAYER_CANCELED: {

					$this->getResponse()
							->output('<h1>YOU CANCELED THE PAYMENT!!!</h1>');
				} break;

			case TransactionStatus::PENDING: {

					$this->getResponse()
							->output('<h1>PAYMENT STILL PENDING!!!</h1>');
				} break;

			case TransactionStatus::STARTED: {

					$this->getResponse()
							->output('<h1>YOU STARTED PAYMENT PROCEDURE!!!</h1>');
				} break;

			default: {

					$this->getResponse()
							->output('<h1>!!! ERROR #' . $transactionStatus . ' !!!</h1>');
				}
		}
	}

	protected function submitRecurringOrder()
	{
		$order = $this->recurringOrder;

		$response = $this->getResponse();

		$orderProvider = $this->getOrderProvider();

		$paymentProvider = $this->getPaymentProvider();

		if ($paymentProvider->validateRecurringOrder($order)) {

			$paymentProvider->processRecurringOrder($order, $response);

			$orderProvider->store($order);
		} else {

			throw new Exception\RuntimeException('Recurring order validation failed.');
		}
	}

	/**
	 * @param string $isoCode
	 * @return Curreny
	 */
	protected function getCurrencyByIsoCode($isoCode)
	{
		$currencyProvider = new CurrencyProvider();
		$currency = $currencyProvider->getCurrencyByIsoCode($isoCode);

		return $currency;
	}

	/**
	 * @return Locale
	 */
	protected function getCurrentLocale()
	{
		$lm = ObjectRepository::getLocaleManager($this);
		$currentLocale = $lm->getCurrent();

		return $currentLocale;
	}

}
