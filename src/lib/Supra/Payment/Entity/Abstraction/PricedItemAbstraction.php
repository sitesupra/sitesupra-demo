<?php

namespace Supra\Payment\Entity\Abstraction;

use Supra\Database;
use Supra\Payment\Entity\Currency\Currency;
use \DateTime;
use Supra\Payment\Product\ProductAbstraction;
use Supra\Locale\LocaleInterface;
use Supra\Payment\Entity\Order\Order;

/**
 * @MappedSuperclass
 * @HasLifecycleCallbacks
 */
abstract class PricedItemAbstraction extends Database\Entity
{

	/**
	 * @Column(type="decimal", precision=10, scale=2, nullable=false)
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

	public function getDescription(LocaleInterface $locale = null)
	{
		return 'Some description';
	}

	/**
	 * @prePersist
	 */
	public function autoCreationTime()
	{
		$this->creationTime = new DateTime('now');
	}

	/**
	 * @preUpdate
	 * @prePersist
	 */
	public function autoModificationTime()
	{
		$this->modificationTime = new DateTime('now');
	}

	public function getClassName()
	{
		return get_called_class();
	}

}
