<?php

namespace Supra\Payment\Entity\Order;

use \DateTime;
use Supra\Locale\Locale;
use Supra\ObjectRepository\ObjectRepository;

/**
 * @Entity
 */
class OrderPaymentProviderItem extends OrderItem
{

	/**
	 * @Column(type="string", nullable=false)
	 * @var string
	 */
	protected $paymentProviderId;

	/**
	 * @Column(type="string", nullable=true)
	 * @var string
	 */
	protected $description;

	function __construct()
	{
		parent::__construct();

		$this->price = 42.0;
	}

	/**
	 * @param Locale $locale
	 * @return string 
	 */
	public function getDescription(Locale $locale = null)
	{
		$paymentProviderCollection = ObjectRepository::getPaymentProviderCollection($this);

		$paymentProvider = $paymentProviderCollection->get($this->paymentProviderId);

		return $paymentProvider->getOrderItemDescription($this->order, $locale);
	}

	/**
	 * @param type $paymentProviderId 
	 */
	public function setPaymentProviderId($paymentProviderId)
	{
		$this->paymentProviderId = $paymentProviderId;
	}

	/**
	 * @return string
	 */
	public function getPaymentProviderId()
	{
		return $this->paymentProviderId;
	}

}

