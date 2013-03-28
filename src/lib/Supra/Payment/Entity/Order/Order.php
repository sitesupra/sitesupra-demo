<?php

namespace Supra\Payment\Entity\Order;

use \DateTime;
use Supra\Database;
use Supra\Locale\LocaleInterface;
use Supra\Payment\Entity\Currency\Currency;
use Supra\Payment\Order\OrderStatus;
use Supra\Payment\Product\ProductAbstraction;
use Supra\Payment\Entity\Order\OrderItem;
use Supra\Payment\Entity\Order\OrderProductItem;
use Supra\Payment\Entity\Order\OrderPaymentProviderItem;
use Doctrine\Common\Collections\ArrayCollection;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Payment\Entity\Abstraction\PaymentEntity;

/**
 * @Entity
 * @HasLifecycleCallbacks
 * @InheritanceType("SINGLE_TABLE")
 * @DetachedDiscriminators
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({"shop" = "ShopOrder", "recurring" = "RecurringOrder"})
 */
abstract class Order extends Database\Entity
{

	/**
	 * @OneToMany(targetEntity="OrderItem", mappedBy="order", cascade={"persist"})
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
	protected $initiatorUrl;

	/**
	 * @Column(type="string", nullable=true, length=40)
	 * @var string
	 */
	protected $localeId;
	
	/**
	 * @Column(type="integer", nullable=true)
	 * @var integer
	 */
	protected $vat;
	
	/**
	 * @Column(type="integer", nullable=true)
	 * @var integer
	 */
	protected $invoiceSequenceNr;
	

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
	 * @param LocaleInterface $locale
	 */
	public function setLocale(LocaleInterface $locale)
	{
		$this->localeId = $locale->getId();
	}

	/**
	 * @return LocaleInterface
	 */
	public function getLocale()
	{
		$localeManager = ObjectRepository::getLocaleManager($this);

		if (empty($this->localeId)) {
			$this->localeId = $localeManager->getCurrent()->getId();
		}

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
	public function getInitiatorUrl()
	{
		return $this->initiatorUrl;
	}

	/**
	 * @param string $initiatorUrl 
	 */
	public function setInitiatorUrl($initiatorUrl)
	{
		$this->initiatorUrl = $initiatorUrl;
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
		
		// Value added tax
		if ($this->vat > 0) {
			$tax = ($total * $this->vat) / 100;
			$total = $total + $tax;
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
		
		// Value added tax
		if ($this->vat > 0) {
			$tax = ($total * $this->vat) / 100;
			$total = $total + $tax;
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
	 * @param LocaleInterface $locale
	 */
	public function updateLocale(LocaleInterface $locale)
	{
		if ($this->locale != $locale->getId()) {
			$this->setLocale($locale);
		}
	}

	/**
	 * @param integer $vatAmount
	 * @throws \LogicException
	 */
	public function setVat($vatRate) 
	{
		$vat = (int) $vatRate;
		
		if ($vat < 0) {
			throw new \LogicException("Negative VAT rate value passed");
		}
		
		$this->vat = $vat;
	}

	/**
	 * @return integer
	 */
	public function getVat()
	{
		return $this->vat;
	}
	
	/**
	 * @return string
	 */
	public function getInvoiceNumber()
	{
		return sprintf('%s-%i', $this->creationTime->format('dmY'), $this->invoiceSequenceNr);
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

	abstract public function isPaid();

	abstract public function getPaymentEntityId();

	abstract public function getPaymentProviderId();

	abstract public function addToPaymentEntityParameters($phaseName, $data);

	abstract public function getPaymentEntityParameterValue($phaseName, $name);
}
