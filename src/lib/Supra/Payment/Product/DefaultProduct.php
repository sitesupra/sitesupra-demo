<?php

namespace Supra\Payment\Product;

use Supra\Payment\Entity\Currency\Currency;
use \DateTime;

class DefaultProduct extends ProductAbstraction
{

	/**
	 * Prices for 1 item of product per currency ISO code
	 * @var array
	 */
	protected $prices;
	
	/**
	 * @var string
	 */
	protected $id;

	/**
	 * @param string $id 
	 */
	public function setId($id)
	{
		$this->id = $id;
	}

	/**
	 * @return string
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Sets prices for 1 item of this product.
	 * @param array $prices 
	 */
	public function setPrices($prices)
	{
		$this->prices = $prices;
	}

	/**
	 * Returns price for $amount of items for in currency $currency.
	 * @param float $amount
	 * @param Currency $currency
	 * @param DateTime $when 
	 */
	function getPrice($amount, Currency $currency, DateTime $when = null)
	{
		$isoCode = $currency->getIsoCode();

		if (empty($this->prices[$isoCode])) {
			throw new Exception\RuntimeException('No price for this currency "' . $isoCode . '"');
		}

		return $amount * $this->prices[$isoCode];
	}

}
