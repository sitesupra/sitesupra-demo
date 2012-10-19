<?php

namespace Project\Payment\Dengi\Backend;

use Project\Payment\Dengi;
use Supra\Payment\Entity\Order\Order;
use Supra\Response\ResponseInterface;

abstract class BackendAbstraction
{

	/**
	 * @var string
	 */
	protected $modeType;

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var string
	 */
	protected $currencyCode;

	/**
	 *
	 * @var Dengi\PaymentProvider;
	 */
	protected $paymentProvider;

	/**
	 *
	 * @var Order
	 */
	protected $order;

	/**
	 * @return string
	 */
	public function getModeType()
	{
		return $this->modeType;
	}

	/**
	 * @param string $modeType
	 */
	public function setModeType($modeType)
	{
		$this->modeType = $modeType;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @param string $name
	 */
	public function setName($name)
	{
		$this->name = $name;
	}

	/**
	 * @return string
	 */
	public function getCurrencyCode()
	{
		return $this->currencyCode;
	}

	/**
	 * @param string $currencyCode
	 */
	public function setCurrencyCode($currencyCode)
	{
		$this->currencyCode = $currencyCode;
	}

	/**
	 * @return Dengi\PaymentProvide
	 */
	public function getPaymentProvider()
	{
		return $this->paymentProvider;
	}

	/**
	 * @param Dengi\PaymentProvider $paymentProvider
	 */
	public function setPaymentProvider(Dengi\PaymentProvider $paymentProvider)
	{
		$this->paymentProvider = $paymentProvider;
	}

	/**
	 * @return Order
	 */
	public function getOrder()
	{
		return $this->order;
	}

	/**
	 * @param Order $order
	 */
	public function setOrder(Order $order)
	{
		$this->order = $order;
	}

	/**
	 * @return string
	 */
	abstract public function getFormElements();

	/**
	 * @param array $formData
	 */
	abstract public function validateForm($formData);

	/**
	 * 
	 */
	abstract public function proxyAction($formData, ResponseInterface $response);

	/**
	 * 
	 */
	abstract public function returnAction();

	/**
	 * 
	 */
	abstract public function notificationAction();

	/**
	 * @return string
	 */
	static function CN()
	{
		return get_called_class();
	}

}
