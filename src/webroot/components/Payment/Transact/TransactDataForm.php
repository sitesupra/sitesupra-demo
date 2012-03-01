<?php

namespace Project\Payment\Transact;

use Supra\Controller\Pages\BlockController;
use Supra\Controller\Pages\Request\PageRequestEdit;
use Supra\Controller\Pages\Request\PageRequestView;
use Supra\Html\HtmlTag;
use Project\Payment\Transact;
use Supra\Payment\Entity\Order;
use Supra\Payment\Provider\PaymentProviderAbstraction;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Payment\Order\OrderProvider;
use Doctrine\ORM\EntityManager;

class TransactDataForm extends BlockController
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
	 * @var array
	 */
	protected $defaultValues = array(
		'name_on_card' => 'Trololo Desa',
		'street' => 'Siers 44',
		'zip' => 'LV-1050',
		'city' => 'Riga',
		'country' => 'LV',
		'state' => 'NA',
		'email' => 'oiasoijos9393@videinfra.com',
		'phone' => '447271783',
		'cc' => '5413330000000027',
		'cvv' => '589',
		'expire' => '01/13',
		'bin_name' => 'BinBinBin',
		'bin_phone' => '111222333',
	);

	private function getFormInputMetadata()
	{
		$formInputs = array();

		$formInputs['name_on_card'] = 'Name on Card';
		$formInputs['street'] = 'Street';
		$formInputs['zip'] = 'Zip';
		$formInputs['city'] = 'City';
		$formInputs['country'] = 'Country';
		$formInputs['state'] = 'State';
		$formInputs['email'] = 'Email';
		$formInputs['phone'] = 'Phone';

		$paymentProvider = $this->getPaymentProvider();

		if ( ! $paymentProvider->getGatewayCollects()) {

			$formInputs['cc'] = 'Card number';
			$formInputs['cvv'] = 'Card CCV number';
			$formInputs['expire'] = 'Card expiration date, MM/YY';

			$formInputs['bin_name'] = 'BIN name';
			$formInputs['bin_phone'] = 'BIN phone';
		} else {
			
		}

		return $formInputs;
	}

	public function execute()
	{
		$request = $this->getRequest();

		if ($request instanceof PageRequestView) {

			$this->processViewRequest();
		} else {

			$this->processEditRequest();
		}

		$this->getResponse()
				->outputTemplate('transactForm.html.twig');
	}

	protected function processEditRequest()
	{
		$response = $this->getResponse();

		$response->assign('formElements', $this->buildFormElements());
		$response->assign('action', '#');
	}

	protected function processViewRequest()
	{
		$request = $this->getRequest();
		$response = $this->getResponse();

		$postData = $request->getPost()->getArrayCopy();
		$response->assign('formElements', $this->buildFormElements($postData));

		$order = $this->getOrder();
		$paymentProvider = $this->getPaymentProvider();

		$response->assign('order', $order);

		$returnUrl = $paymentProvider->getProxyActionReturnFormDataUrl($order);

		$response->assign('action', $returnUrl);
	}

	private function buildFormElements($inputValues = array())
	{
		$formInputMetadata = $this->getFormInputMetadata();

		$formElements = array();

		foreach (array_keys($formInputMetadata) as $name) {

			$label = new HtmlTag('label');
			$label->setAttribute('for', $name);
			$label->setContent($this->getPropertyValue($name));
			$formElements[] = $label->toHtml();

			$input = new HtmlTag('input');
			$input->setAttribute('type', 'text');
			$input->setAttribute('id', $name);
			$input->setAttribute('name', $name);

			if ( ! empty($inputValues)) {
				$input->setAttribute('value', $inputValues[$name]);
			} else if ( ! empty($this->defaultValues[$name])) {
				$input->setAttribute('value', $this->defaultValues[$name]);
			}
			
			$formElements[] = $input->toHtml();

			$br = new HtmlTag('br');
			$formElements[] = $br->toHtml();
		}

		return $formElements;
	}

	public function getPropertyDefinition()
	{
		$formInputMetadata = $this->getFormInputMetadata();

		$contents = array();

		foreach ($formInputMetadata as $name => $description) {

			$item = new \Supra\Editable\String('"' . $description . '" label');
			$item->setDefaultValue($description);
			$contents[$name] = $item;
		}

		return $contents;
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

	protected function getOrder()
	{
		if (empty($this->order)) {
			$this->order = $this->fetchOrderFromRequest();
		}

		return $this->order;
	}

	/**
	 * @return Transact\PaymentProvider
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

}
