<?php

namespace Project\Payment\Dengi;

use Supra\Controller\Pages\BlockController;
use Supra\Controller\Pages\Request\PageRequestEdit;
use Supra\Controller\Pages\Request\PageRequestView;
use Supra\Html\HtmlTag;
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
	 * @var array
	 */
	protected $defaultValues = array();
	protected $modeTypeDefinitions = array(
		'48' => array('title' => 'ВТБ24'),
		'76' => array('title' => 'Интернет-банк «Альфа-Клик»'),
		'61' => array('title' => 'Промсвязьбанк'),
		'30' => array('title' => 'IntellectMoney'),
		'13' => array('title' => 'MoneyMail'),
		'14' => array('title' => 'QIWI Кошелек'),
		'9' => array('title' => 'RBK Money'),
		'74' => array('title' => 'TeleMoney'),
		'65' => array('title' => 'Ukash'),
		'80' => array('title' => 'W1 - Единый кошелёк '),
		'15' => array('title' => 'WebCreds'),
		'2' => array('title' => 'WebMoney RUB'), // !
		'204' => array('title' => 'Web-кошелек ПСКБ'),
		'68' => array('title' => 'WellPay!'),
		'73' => array('title' => 'Yota.Деньги'),
		'32' => array('title' => 'Деньги@Mail.ru'),
		'43' => array('title' => 'Дом.ru'),
		'12' => array('title' => 'Карты Деньги Online'),
		'66' => array('title' => 'Куппи'),
		'79' => array('title' => 'Твинго (Ваши Деньги)'),
		'7' => array('title' => 'Яндекс.Деньги'), //
		'75' => array('title' => 'CONTACT'),
		'54' => array('title' => 'Rapida'),
		'87' => array('title' => 'Western Union'),
		'62' => array('title' => 'Евросеть'),
		'115' => array('title' => 'СберБанк Спасибо'),
		'11' => array('title' => 'SMS'),
		'56' => array('title' => 'Терминалы Comepay'),
		'246' => array('title' => 'Терминалы CyberPlat'),
		'71' => array('title' => 'Терминалы SberPlat'),
		'42' => array('title' => 'Терминалы Кассира.Нет'),
		'18' => array('title' => 'Терминалы ОСМП'),
		'70' => array('title' => 'Терминалы Мобил Элемент'),
		'37' => array('title' => 'Терминалы Свободная Касса'),
		'64' => array('title' => 'Терминалы Элекснет'),
		'117' => array('title' => 'Ямальская Платежная Компания'),
		'194' => array('title' => 'BankLink (SwedBank)'),
		'140' => array('title' => 'Dresdner Bank Internetbanking'),
		'142' => array('title' => 'Dutch Direct Debit'),
		'146' => array('title' => 'ELBA Internet Payment'),
		'160' => array('title' => 'Fast Bank Transfer'),
		'162' => array('title' => 'German Direct Debit'),
		'168' => array('title' => 'Internet Cheque'),
		'182' => array('title' => 'Nordea Bank'),
		'196' => array('title' => 'Nordea Bank Finland'),
		'198' => array('title' => 'Nordea Bank Sweden'),
		'184' => array('title' => 'Partner Online Paying'),
	);

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

		$response->assign('formElements', $this->buildFormElements());
		$response->assign('action', '#');
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
		$response->assign('order', $order);

		$session = $paymentProvider->getSessionForOrder($order);

		$postData = $request->getPost()->getArrayCopy();

		$response->assign('formElements', $this->buildFormElements($postData));

		if ( ! empty($session->errorMessages)) {

			$response->assign('errorMessages', $session->errorMessages);

			unset($session->errorMessages);
		}

		$returnUrl = $paymentProvider->getDataFormReturnUrl($order);

		$response->assign('action', $returnUrl);
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

	/**
	 * @return array
	 */
	public static function getPropertyDefinition()
	{
		$contents = array();

		return $contents;
	}

}
