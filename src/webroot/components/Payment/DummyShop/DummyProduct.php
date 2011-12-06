<?php

namespace Project\Payment\DummyShop;

use Supra\Payment\Product\ProductAbstraction;
use Supra\Payment\Entity\Currency\Currency;
use \DateTime;
use Supra\Locale\Locale;

class DummyProduct extends ProductAbstraction
{

	function __construct($id)
	{
		$this->id = $id;
	}

	/**
	 * @var string
	 */
	protected $id;

	public function getId()
	{
		return $this->id;
	}

	public function getProviderClass()
	{
		return DummyProductProvider::CN();
	}

	public function getPrice($quantity, Currency $currency, DateTime $when = null)
	{
		return 3.50 * $quantity;
	}

	public function getDescription(Locale $locale = null)
	{
		return 'Trololo Product ' . $this->id;
	}

}

