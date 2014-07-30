<?php

namespace Supra\Payment\Entity\Order;

use Supra\Locale\LocaleInterface;
use \DateTime;
use Supra\Payment\Entity\Currency\Currency;
use Supra\Payment\Product\ProductAbstraction;
use Supra\Payment\Product\ProductProviderAbstraction;

/**
 * @Entity
 */
class OrderProductItem extends OrderItem
{
	/**
	 * @Column(type="integer", nullable=false)
	 * @var integer
	 */
	protected $quantity;

	/**
	 * @Column(type="string", nullable=false)
	 * @var string
	 */
	protected $productId;

	/**
	 * @Column(type="string", nullable=false)
	 * @var string
	 */
	protected $productProviderClass;

	/**
	 * @var ProductAbstraction
	 */
	protected $product;

	public function __construct()
	{
		parent::__construct();
		$this->quantity = 1;
	}

	/**
	 * @return integer
	 */
	public function getQuantity()
	{
		return $this->quantity;
	}

	/**
	 * @param integer $quantity
	 */
	public function setQuantity($quantity)
	{
		$this->quantity = $quantity;
	}

	/**
	 * @return string
	 */
	public function getProductId()
	{
		return $this->productId;
	}

	/**
	 * @return string
	 */
	public function getProductProviderClass()
	{
		return $this->productProviderClass;
	}

	/**
	 * @param ProductAbstraction $product
	 */
	public function setProduct(ProductAbstraction $product)
	{
		$this->productId = $product->getId();
		$this->productProviderClass = $product->getProviderClass();
	}

	/**
	 * @return ProductAbstraction
	 */
	public function getProduct()
	{
		if (empty($this->product)) {

			$productProviderClasss = $this->getProductProviderClass();

			/* @var $productProvider ProductProviderAbstraction */
			$productProvider = new $productProviderClasss();

			/* @var $product ProductAbstraction */
			$this->product = $productProvider->getById($this->getProductId());
		}

		return $this->product;
	}

	/**
	 * @param Currency $currency
	 * @param DateTime $when 
	 */
	public function setPriceFromProduct(Currency $currency, DateTime $when = null)
	{
		$product = $this->getProduct();

		$this->price = $product->getPrice($this->quantity, $currency, $when);
	}

	/**
	 * @param LocaleInterface $locale
	 * @return string
	 */
	public function getDescription(LocaleInterface $locale = null)
	{
		$product = $this->getProduct();

		if (empty($locale)) {
			$locale = $this->order->getLocale();
		}

		return $product->getDescription($locale);
	}

}

