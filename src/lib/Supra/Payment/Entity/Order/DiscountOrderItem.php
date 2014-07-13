<?php

namespace Supra\Payment\Entity\Order;

use Supra\Locale\LocaleInterface;

/**
 * @Entity
 */
class DiscountOrderItem extends OrderItem
{
	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $description;

	/**
	 * @param float $price 
	 */
	public function setPrice($price)
	{
		throw new \RuntimeException("For DiscountOrderItem, please, use the setDiscount() method instead");
	}
	
	/**
	 * @param string $description
	 */
	public function setDescription($description)
	{
		$this->description = $description;
	}

	/**
	 * @param LocaleInterface $locale
	 * @return string
	 */
	public function getDescription(LocaleInterface $locale = null)
	{
		return $this->description;
	}
	
	/**
	 * @param float $discount
	 */
	public function setDiscount($discount) 
	{
		if ($discount < 0) {
			throw new \InvalidArgumentException("Discount should not be negative");
		}
		
		$reversedAmount = $discount * (-1);
		if ($reversedAmount > 0) {
			// Wat?
			throw new \RuntimeException("Discount amount should be negative, got '{$reversedAmount}'");
		}
		
		parent::setPrice($reversedAmount);
	}

	/**
	 * Wrapper around getPrice() just because of name
	 * @return float
	 */
	public function getDiscount()
	{
		return $this->getPrice();
	}
}
