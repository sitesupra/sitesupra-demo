<?php

namespace Supra\Payment\Entity\Order;

use Supra\Database;
use Supra\Payment\Entity\Currency\Currency;
use \DateTime;
use Supra\Payment\Product\ProductAbstraction;
use Supra\Locale\Locale;

/**
 * @Entity
 * @HasLifecycleCallbacks
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({"product" = "OrderProductItem", "paymentProvider" = "OrderPaymentProviderItem"})
 */
abstract class OrderItem extends Database\Entity
{

	/**
	 * @ManyToOne(targetEntity="Order", inversedBy="items")
	 * @JoinColumn(name="orderId", referencedColumnName="id")
	 */
	protected $order;

	/**
	 * @Column(type="decimal", precision="10", scale="2", nullable=false)
	 * @var float
	 */
	protected $price;

	/**
	 * @Column(type="datetime")
	 * @var DateTime
	 */
	protected $creationTime;

	/**
	 * @Column(type="datetime")
	 * @var DateTime
	 */
	protected $modificationTime;

	/**
	 * @return float
	 */
	public function getPrice()
	{
		return $this->price;
	}

	/**
	 * @param float $price 
	 */
	public function setPrice($price)
	{
		$this->price = $price;
	}

	/**
	 * @param Order $order 
	 */
	public function setOrder(Order $order)
	{
		$this->order = $order;
	}

	public function getDescription(Locale $locale = null)
	{
		return 'Some description';
	}

	/**
	 * @prePersist
	 */
	public function autoCretionTime()
	{
		$this->creationTime = new DateTime('now');
		$this->modificationTime = new DateTime('now');
	}

	/**
	 * @preUpdate
	 */
	public function autoModificationTime()
	{
		$this->modificationTime = new DateTime('now');
	}

	public function getClassName()
	{
		return get_called_class();
	}

	public function validateAddToOrder(Order $order)
	{
		return true;
	}

}

