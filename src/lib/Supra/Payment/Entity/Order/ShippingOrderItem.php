<?php

namespace Supra\Payment\Entity\Order;

use Supra\Locale\LocaleInterface;

/**
 * @Entity
 */
class ShippingOrderItem extends OrderItem
{
	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $description;

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
}
