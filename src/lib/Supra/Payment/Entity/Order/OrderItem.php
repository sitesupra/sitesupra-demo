<?php

namespace Supra\Payment\Entity\Order;

use Supra\Database;
use Supra\Payment\Entity\Currency\Currency;
use \DateTime;

/**
 * @Entity
 */
class OrderItem extends Database\Entity
{

	/**
	 * @ManyToOne(targetEntity="Order")
	 * @JoinColumn(name="orderId", referencedColumnName="id")
	 */
	protected $orderId;
	
	/**
	 * @Column(type="integer", nullable=false)
	 * @var integer
	 */
	protected $amount;

	/**
	 * @Column(type="string", nullable=false)
	 * @var string
	 */
	protected $productId;

	/**
	 * @Column(type="string", nullable=false)
	 * @var string
	 */
	protected $productClass;

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
	 * @return string
	 */
	public function getProductId()
	{
		return $this->productId;
	}

	/**
	 * @return string
	 */
	public function getProductClass()
	{
		return $this->productClass;
	}

	/**
	 * @return integer
	 */
	public function getAmount()
	{
		return $this->amount;
	}

	/**
	 * @return float
	 */
	public function getPrice()
	{
		return $this->price;
	}

}

