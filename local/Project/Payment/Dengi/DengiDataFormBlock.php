<?php

namespace Project\Payment\Dengi;

use Supra\Controller\Pages\BlockController;
use Supra\Controller\Pages\Request\PageRequestView;
use Project\Payment\Dengi;
use Supra\Payment\Entity\Order;
use Supra\Payment\Provider\PaymentProviderAbstraction;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Payment\Order\OrderProvider;
use Doctrine\ORM\EntityManager;

class DengiDataFormBlock extends BlockController
{

	/**
	 * @var OrderProvider
	 */
	protected $orderProvider;

	/**
	 * @var EntityManager
	 */
	protected $entityManager;

	/**
	 * @var Order\Order
	 */
	protected $order;
	
	/**
	 * @return OrderProvider
	 */
	protected function getOrderProvider()
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
	 * @param OrderProvider $orderProvider 
	 */
	protected function setOrderProvider(OrderProvider $orderProvider)
	{
		$this->orderProvider = $orderProvider;
	}

	/**
	 * @return EntityManage
	 */
	protected function getEntityManager()
	{
		if (empty($this->entityManager)) {
			$this->entityManager = ObjectRepository::getEntityManager($this);
		}

		return $this->entityManager;
	}

	/**
	 * @param EntityManager $entityManager 
	 */
	protected function setEntityManager(EntityManager $entityManager)
	{
		$this->entityManager = $entityManager;
	}

	/**
	 * @return Dengi\PaymentProvider
	 */
	protected function getPaymentProvider()
	{
		$order = $this->getOrder();

		$paymentProviderId = $order->getPaymentProviderId();

		$paymentProviderCollection = ObjectRepository::getPaymentProviderCollection($this);

		$paymentProvider = $paymentProviderCollection->get($paymentProviderId);

		return $paymentProvider;
	}

	/**
	 * @return Order\Order
	 */
	protected function fetchOrderFromRequest()
	{
		$request = $this->getRequest();

		$orderId = $request->getParameter(PaymentProviderAbstraction::REQUEST_KEY_ORDER_ID, null);
		if (empty($orderId)) {
			throw new Exception\RuntimeException('Could not fetch order id.');
		}

		$orderProvider = $this->getOrderProvider();

		$order = $orderProvider->findOrder($orderId);

		if (empty($order)) {
			throw new Exception\RuntimeException('Could not find order for id "' . $orderId . '".');
		}

		return $order;
	}

	/**
	 * @return type
	 */
	protected function getOrder()
	{
		if (empty($this->order)) {
			$this->order = $this->fetchOrderFromRequest();
		}

		return $this->order;
	}

	/**
	 * @var array
	 */
	public function doExecute()
	{
		$request = $this->getRequest();

		if ($request instanceof PageRequestView) {

			$this->processViewRequest();
		} else {

			$this->processEditRequest();
		}

		$this->getResponse()
				->outputTemplate('dengiDataForm.html.twig');
	}

	/**
	 * 
	 */
	protected function processEditRequest()
	{
		$response = $this->getResponse();

		$response->assign('formElements', array())
			->assign('action', '#')
			->assign('errorMessages', array());
	}

	/**
	 * 
	 */
	protected function processViewRequest()
	{
		$paymentProvider = $this->getPaymentProvider();

		$request = $this->getRequest();
		$response = $this->getResponse();
		
		$order = $this->getOrder();
		$status = $order->getStatus();
		
		if ($status == \Supra\Payment\Order\OrderStatus::FINALIZED) {
			$response->assign('order', $order);

			$session = $paymentProvider->getSessionForOrder($order);

			$postData = $request->getPost()->getArrayCopy();

			$response->assign('formElements', $this->buildFormElements($postData));

			if ( ! empty($session->errorMessages)) {

				$response->assign('errorMessages', $session->errorMessages);
				unset($session->errorMessages);
			} else {

				$response->assign('errorMessages', array());
			}

			$returnUrl = $paymentProvider->getDataFormReturnUrl($order);

			$response->assign('action', $returnUrl);
			
			
			if ($order instanceof \Supra\Payment\Entity\Order\ShopOrder) {
				
				/* @var $order \Supra\Payment\Entity\Order\ShopOrder */
				$items = $order->getProductItems();
				$productItem = $items[0];
				
				if ($productItem instanceof \Supra\Payment\Entity\Order\OrderProductItem) {
					
					$product = $productItem->getProduct();
					
					if ($product instanceof \Project\Entity\Operation\OperationInstallByPayment) {
						
						$lm = ObjectRepository::getLocaleManager($this);
						$locale = $lm->getCurrent();
						
						/* @var $product \Project\Entity\Operation\OperationInstallByPayment */
						$appName = $product->getApplicationName($locale);
						$appPrice = $productItem->getPrice();
						$currency = $order->getCurrency();
						
						if ($currency instanceof \Supra\Payment\Entity\Currency\Currency) {
							$appCurrency = $currency->getIso4217Code();
							
							$response
								->assign('appName', $appName)
								->assign('appPrice', $appPrice)
								->assign('appCurrency', $appCurrency);
						}
					}
				}
			}
		}
		
		$response->assign('orderStatus', $status);
	}

	/**
	 * 
	 * @param array $inputValues
	 * @return array
	 */
	private function buildFormElements($inputValues = array())
	{
		$formElements = array();

		$currentModeType = empty($inputValues['mode_type']) ? null : $inputValues['mode_type'];

		$paymentProvider = $this->getPaymentProvider();

		$backends = $paymentProvider->getBackends();

		foreach ($backends as $backend) {
			/* @var $backend Backend\BackendAbstraction */

			$isCurrent = $currentModeType == $backend->getModeType();

			$formElements = array_merge($formElements, $backend->getFormElements($isCurrent));
		}

		return $formElements;
	}

}
