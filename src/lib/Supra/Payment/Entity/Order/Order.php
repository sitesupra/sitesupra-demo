<?php

namespace Supra\Payment\Entity\Order;

use \DateTime;
use Supra\Database;
use Supra\Locale\Locale;
use Supra\Payment\Entity\Currency\Currency;
use Supra\Payment\Order\OrderStatus;
use Supra\Payment\Product\ProductAbstraction;
use Supra\Payment\Product\ProductProviderAbstraction;
use Supra\Payment\Entity\Order\OrderItem;
use Supra\Payment\Entity\Order\OrderProductItem;
use Supra\Payment\Entity\Order\OrderPaymentProviderItem;
use Doctrine\Common\Collections\ArrayCollection;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Payment\Entity\Abstraction\PaymentEntity;/**
 * @Entity
 * @HasLifecycleCallbacks
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({"shop" = "ShopOrder", "recurring" = "RecurringOrder"})
 */

abstract class Order extends Database\Entity
{

	/**
	 * @OneToMany(targetEntity="OrderItem", mappedBy="order")
	 * @var ArrayCollection
	 */
	protected $items;

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
	 * @ManyToOne(targetEntity="Supra\Payment\Entity\Currency\Currency")
	 * @JoinColumn(name="currencyId", referencedColumnName="id")
	 * @var Currency
	 */
	protected $currency;

	/**
	 * @Column(type="string", nullable=false)
	 * @var string
	 */
	protected $userId;

	/**
	 * @Column(type="integer", nullable=false)
	 * @var integer
	 */
	protected $status;

	/**
	 * @Column(type="string", nullable=true)
	 * @var string
	 */
	protected $returnUrl;

	/**
	 * @Column(type="string", nullable=true, length=40)
	 * @var string
	 */
	protected $localeId;

	function __construct()
	{
		parent::__construct();

		$this->status = OrderStatus::OPEN;
		$this->items = new ArrayCollection();
	}

	/**
	 * Returns order items.
	 * @return array
	 */
	public function getItems()
	{
		return $this->items;
	}

	/**
	 * @param array $items 
	 */
	public function setItems($items)
	{
		$this->items = $items;
	}

	/**
	 * @param Locale $locale 
	 */
	public function setLocale(Locale $locale)
	{
		$this->localeId = $locale->getId();
	}

	/**
	 * @return Locale
	 */
	public function getLocale()
	{
		$localeManager = ObjectRepository::getLocaleManager($this);

		$locale = $localeManager->getLocale($this->localeId);

		return $locale;
	}

	/**
	 * @return Currency
	 */
	public function getCurrency()
	{
		return $this->currency;
	}

	/**
	 * @return string
	 */
	public function getReturnUrl()
	{
		return $this->returnUrl;
	}

	/**
	 * @param string $returnUrl 
	 */
	public function setReturnUrl($returnUrl)
	{
		$this->returnUrl = $returnUrl;
	}

	/**
	 * @param Currency $currency
	 */
	function setCurrency(Currency $currency)
	{
		$this->currency = $currency;
	}

	/**
	 * @param string $userId
	 */
	public function setUserId($userId)
	{
		$this->userId = $userId;
	}

	/**
	 * @return string
	 */
	public function getUserId()
	{
		return $this->userId;
	}

	/**
	 * Returns sum of all prices of items in order.
	 * @return float
	 */
	public function getTotal()
	{
		$total = 0;

		foreach ($this->items as $item) {
			/* @var $item OrderItem */

			$total = $total + $item->getPrice();
		}

		return $total;
	}

	/**
	 * @return float
	 */
	public function getTotalForProductItems()
	{
		$total = 0.0;

		foreach ($this->items as $item) {

			if ($item instanceof OrderProductItem) {
				$total = $total + $item->getPrice();
			}
		}

		return $total;
	}

	/**
	 * Sets status for this order. See Order\OrderStatus class for more details.
	 * @param integer $status 
	 */
	public function setStatus($status)
	{
		OrderStatus::validate($status);
		$this->status = $status;
	}

	/**
	 * Returns status or this order. See Order\OrderStatus class for more details.
	 * @return integer
	 */
	public function getStatus()
	{
		return $this->status;
	}

	/**
	 * @param OrderItem $item
	 */
	public function addItem(OrderItem $item)
	{
		if ($item->validateAddToOrder($this)) {

			$item->setOrder($this);
			$this->items[] = $item;
		}
	}

	/**
	 * @prePersist
	 */
	public function autoCretionTime()
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

	/**
	 * @param string $productId 
	 * @return OrderItem
	 */
	public function getOrderItemByProduct(ProductAbstraction $product)
	{
		foreach ($this->items as $item) {
			/* @var $item OrderItem */

			if ($item instanceof OrderProductItem) {

				if ($item->getProductId() == $product->getId() &&
						$item->getProductProviderClass() == $product->getProviderClass()
				) {
					return $item;
				}
			}
		}

		$newOrderItem = new OrderProductItem();

		$newOrderItem->setOrder($this);
		$newOrderItem->setProduct($product);
		$this->items[] = $newOrderItem;

		return $newOrderItem;
	}

	/**
	 * @param string $paymentProviderId
	 * @return OrderPaymentProviderItem 
	 */
	public function getOrderItemByPayementProvider($paymentProviderId = null)
	{
		foreach ($this->items as $item) {

			if ($item instanceof OrderPaymentProviderItem) {

				if (
						$paymentProviderId == null ||
						$item->getPaymentProviderId() == $paymentProviderId
				) {
					return $item;
				}
			}
		}

		$paymentProviderItem = new OrderPaymentProviderItem();
		$paymentProviderItem->setPaymentProviderId($paymentProviderId);
		$paymentProviderItem->setOrder($this);
		$paymentProviderItem->setPrice(1);

		$this->items[] = $paymentProviderItem;

		return $paymentProviderItem;
	}

	/**
	 * @param OrderItem $orderItem 
	 */
	public function removeOrderItem(OrderItem $orderItem)
	{
		$this->items->removeElement($orderItem);
	}

	/**
	 * @param Locale $locale 
	 */
	public function updateLocale(Locale $locale)
	{
		if ($this->locale != $locale->getId()) {
			$this->setLocale($locale);
		}
	}

	/**
	 * @return array
	 */
	public function getProductItems()
	{
		$result = array();

		foreach ($this->items as $item) {

			if ($item instanceof OrderProductItem) {
				$result[] = $item;
			}
		}

		return $result;
	}

	abstract public function getPaymentProviderId();

	abstract public function addToPaymentEntityParameters($phaseName, $data);
}
