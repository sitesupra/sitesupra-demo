<?php

namespace Supra\Payment\Order;

use Supra\ObjectRepository\ObjectRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Supra\Payment\Transaction\TransactionProvider;
use Supra\Payment\RecurringPayment\RecurringPaymentProvider;
use Supra\Payment\Entity\RecurringPayment\RecurringPayment;
use Supra\Payment\Entity\Order\Order;
use Supra\Payment\Entity\Order\OrderItem;
use Supra\Payment\Entity\Order\OrderProductItem;
use Supra\Payment\Entity\Order\ShopOrder;
use Supra\Payment\Entity\Order\RecurringOrder;
use Supra\Payment\Entity\Transaction\Transaction;
use Supra\Payment\Product\ProductProvider;
use Supra\Payment\Product\ProductAbstraction;
use Supra\Payment\Product\ProductProviderAbstraction;
use Supra\Payment\Provider\PaymentProviderAbstraction;
use Supra\Event\EventManager;
use Supra\User\Entity\User;
use Supra\Payment\Entity\Abstraction\PaymentEntity;

class OrderProvider
{

	/**
	 * @var EntityManager
	 */
	protected $em;

	/**
	 * @var EntityRepository
	 */
	protected $shopOrderRepository;

	/**
	 * @var EntityRepository
	 */
	protected $orderRepository;

	/**
	 * @var EntityRepository
	 */
	protected $recurringOrderRepository;

	/**
	 * @var EntityRepository
	 */
	protected $orderItemRepository;

	/**
	 * @return EntityManager
	 */
	protected function getEntityManager()
	{
		if (empty($this->em)) {
			$this->em = ObjectRepository::getEntityManager($this);
		}

		return $this->em;
	}

	/**
	 * @param EntityManager $em 
	 */
	public function setEntityManager(EntityManager $em)
	{
		$this->em = $em;
		$this->recurringOrderRepository = null;
		$this->shopOrderRepository = null;
		$this->orderRepostory = null;
		$this->orderItemRepositoryr = null;
	}

	/**
	 * @return EntityRepository
	 */
	protected function getOrderItemRepository()
	{
		if (empty($this->orderItemRepository)) {

			$this->orderItemRepository = $this->getEntityManager()
					->getRepository(OrderItem::CN());
		}

		return $this->orderItemRepository;
	}

	/**
	 * @return EntityRepository
	 */
	protected function getOrderRepository()
	{
		if (empty($this->orderRepository)) {

			$this->orderRepository = $this->getEntityManager()
					->getRepository(Order::CN());
		}

		return $this->orderRepository;
	}

	/**
	 * @return EntityRepository
	 */
	protected function getShopOrderRepository()
	{
		if (empty($this->shopOrderRepository)) {

			$this->shopOrderRepository = $this->getEntityManager()
					->getRepository(ShopOrder::CN());
		}

		return $this->shopOrderRepository;
	}

	/**
	 * @return EntityRepository
	 */
	protected function getRecurringOrderRepository()
	{
		if (empty($this->recurringOrderRepository)) {

			$this->recurringOrderRepository = $this->getEntityManager()
					->getRepository(RecurringOrder::CN());
		}

		return $this->recurringOrderRepository;
	}

	/**
	 * Returns order object for order Id.
	 * @param string $orderId
	 * @return Order
	 */
	public function getOrder($orderId)
	{
		$order = $this->findOrder($orderId);

		if (empty($order)) {
			throw new Exception\RuntimeException('Order "' . $orderId . '" is not found.');
		}

		return $order;
	}

	/**
	 * @param String $orderId
	 * @return Order
	 */
	public function findOrder($orderId)
	{
		$order = $this->getOrderRepository()
				->find($orderId);

		return $order;
	}

	public function changeOrderCurrency(Order $order, Currency $newCurrency)
	{
		$em = $this->getEntityManager();

		$orderItems = $order->getItems();

		$newPrices = array();

		$productProvider = new ProductProvider();

		foreach ($orderItems as $orderItem) {
			/* @var $orderItem OrderItem */

			$productId = $orderItem->getProductId();
			$productClass = $orderItem->getProductClass();
			$quantity = $orderItem->getQuantity();

			$product = $productProvider->getProductByIdAndClass($productId, $productClass);

			$newPrices[$orderItem->getId()] = $product->getPrice($quantity, $newCurrency);
		}

		foreach ($orderItems as $orderItem) {

			$orderItemId = $orderItem->getId();

			$newPrice = $newPrices[$orderItemId];

			$orderItem->setPrice($newPrice);

			$em->persist($orderItem);
		}

		$order->setCurrency($newCurrency);
		$em->persist($order);

		$em->flush();
	}

	/**
	 * @return ShopOrder 
	 */
	public function getShopOrderByTransaction(Transaction $transaction)
	{
		$criteria = array(
			'transaction' => $transaction->getId()
		);

		$order = $this->getShopOrderRepository()
				->findOneBy($criteria);

		if (empty($order)) {
			throw new Exception\RuntimeException('Order for transaction "' . $transaction->getId() . '" is not found.');
		}

		return $order;
	}

	/**
	 * @return RecurringOrder
	 */
	public function getRecurringOrderByRecurringPayment(RecurringPayment $recurringPayment)
	{
		$criteria = array(
			'recurringPayment' => $recurringPayment->getId()
		);

		$order = $this->getRecurringOrderRepository()
				->findOneBy($criteria);

		if (empty($order)) {
			throw new Exception\RuntimeException('Order for recurring payment"' . $recurringPayment->getId() . '" is not found.');
		}

		return $order;
	}

	/**
	 * @param PaymentEntity $paymentEntity
	 * @return Order
	 */
	public function getOrderByPaymentEntity(PaymentEntity $paymentEntity)
	{
		$order = null;

		if ($paymentEntity instanceof Transaction) {
			$order = $this->getShopOrderByTransaction($paymentEntity);
		} else if ($paymentEntity instanceof RecurringPayment) {
			$order = $this->getRecurringOrderByRecurringPayment($paymentEntity);
		} else {
			throw new Exception\RuntimeException('Do not know how to get order for payment entity with id "' . $paymentEntity->getId() . '"');
		}

		return $order;
	}

	/**
	 * @param User $user
	 * @return ShopOrder 
	 */
	public function getOpenShopOrderForUser(User $user)
	{
		$criteria = array(
			'userId' => $user->getId(),
			'status' => OrderStatus::OPEN
		);

		$order = $this->getShopOrderRepository()
				->findOneBy($criteria);

		if (empty($order)) {

			$order = new ShopOrder();
			$order->setUserId($user->getId());
			$this->store($order);
		}

		return $order;
	}

	/**
	 * @param User $user
	 * @return RecurringOrder 
	 */
	public function getRecurringOrderForUser(User $user)
	{
		$criteria = array(
			'userId' => $user->getId()
		);

		$order = $this->getRecurringOrderRepository()->findOneBy($criteria);

		return $order;
	}

	/**
	 * @param Order $order
	 */
	public function store(Order $order)
	{
		$em = $this->getEntityManager();

		$itemsToRemove = array();

		foreach ($order->getItems() as $orderItem) {
			/* @var $orderItem OrderItem */

			// If this is product item, and quantity ordered is zero, 
			// remove order item as it makes no sense.
			if (
					$orderItem instanceof OrderProductItem &&
					$orderItem->getQuantity() == 0
			) {
				$em->remove($orderItem);
				$itemsToRemove[] = $orderItem;
			} else {
				$em->persist($orderItem);
			}
		}

		foreach ($itemsToRemove as $orderItemToRemove) {
			$order->removeOrderItem($orderItemToRemove);
		}

		if ($order instanceof ShopOrder) {

			$transaction = $order->getTransaction();

			if ( ! empty($transaction)) {

				$transactionProvider = new TransactionProvider();
				$transactionProvider->setEntityManager($em);

				$transactionProvider->store($transaction);
			}
		} else if ($order instanceof RecurringOrder) {

			$recurringPayment = $order->getRecurringPayment();

			if ( ! empty($recurringPayment)) {

				$recurringPaymentProvider = new RecurringPaymentProvider();
				$recurringPaymentProvider->setEntityManager($em);

				$recurringPaymentProvider->store($recurringPayment);
			}
		} else {
			throw new Exception\RuntimeException('Do not know how to store order of class "' . get_class($order) . '"');
		}

		$em->persist($order);

		$em->flush();
	}

	/**
	 * @return EventManager
	 */
	public function getEventManager()
	{
		$eventManager = $this->getEntityManager()
				->getEventManager();

		return $eventManager;
	}

}
