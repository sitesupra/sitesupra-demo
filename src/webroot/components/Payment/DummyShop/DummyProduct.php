<?php

namespace Project\Payment\DummyShop;

use Supra\Payment\Product\ProductAbstraction;
use Supra\Payment\Entity\Currency\Currency;
use \DateTime;
use Supra\Locale\Locale;

class DummyProduct implements ProductAbstraction
{

	/**
	 * @var string
	 */
	protected $id;

	/**
	 *
	 * @var float
	 */
	protected $pricePerItem = 1.00;

	function __construct($id)
	{
		$this->id = $id;
	}

	public function getProviderClass()
	{
		return DummyProductProvider::CN();
	}

	public function getPrice($quantity, Currency $currency, DateTime $when = null)
	{
		return $this->pricePerItem * $quantity;
	}

	public function getDescription(Locale $locale = null)
	{
		return 'Trololo Product ' . $this->id;
	}

	public function getId()
	{
		return $this->id;
	}

}

