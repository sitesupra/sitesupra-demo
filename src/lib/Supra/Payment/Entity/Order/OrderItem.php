<?php

namespace Supra\Payment\Entity\Order;

use Supra\Payment\Entity\Order\Order;
use Supra\Payment\Entity\Abstraction\PricedItemAbstraction;

/**
 * @Entity
 * @InheritanceType("SINGLE_TABLE")
 * @DetachedDiscriminators
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({
 * 	"product" = "OrderProductItem",
 *	"shipping" = "ShippingOrderItem",
 *	"tax" = "TaxOrderItem",
 *  "discount" = "DiscountOrderItem",
 * 	"paymentProvider" = "OrderPaymentProviderItem"
 * })
 */
abstract class OrderItem extends PricedItemAbstraction
{
	/**
	 * @ManyToOne(targetEntity="Order", inversedBy="items")
	 * @JoinColumn(name="orderId", referencedColumnName="id")
	 * @var Order
	 */
	protected $order;

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

	public function validateAddToOrder(Order $order)
	{
		return true;
	}
}

