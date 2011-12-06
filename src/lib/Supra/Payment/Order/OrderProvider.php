<?php

namespace Supra\Payment\Order;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Payment\Entity\Order\Order;
use Supra\Payment\Entity\Order\OrderItem;
use Supra\Payment\Product\ProductProvider;
use Supra\Payment\Provider\PaymentProviderAbstraction;
use Supra\Payment\Entity\Transaction\Transaction;
use Supra\User\Entity\User;
use Supra\Payment\Product\ProductAbstraction;
use Supra\Payment\Product\ProductProviderAbstraction;
use Supra\Event\EventManager;
use Supra\Payment\Entity\Order\OrderProductItem;

class OrderProvider
{

	/**
	 * @var EntityManager
	 */
	protected $em;

	/**
	 * @var EntityRepository
	 */
	protected $orderRepository;

	/**
	 * @var EntityRepository
	 */
	protected $orderItemRepository;

	/**
	 * @param EntityManager $em 
	 */
	function __construct(EntityManager $em = null)
	{
		if ( ! empty($em)) {
			$this->em = $em;
		}
		else {
			$this->em = ObjectRepository::getEntityManager($this);
		}

		$this->orderRepository = $this->em->getRepository(Order::CN());
		$this->orderItemRepository = $this->em->getRepository(OrderItem::CN());
	}

	/**
	 * Returns order object for order Id.
	 * @param string $orderId
	 * @return Order
	 */
	public function getOrder($orderId)
	{
		$order = $this->orderRepository->find($orderId);

		if (empty($order)) {
			throw new Exception\RuntimeException('Order "' . $orderId . '" not found.');
		}

		return $order;
	}

	public function changeOrderCurrency(Order $order, Currency $newCurrency)
	{
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

			$this->em->persist($orderItem);
		}

		$order->setCurrency($newCurrency);
		$this->em->persist($order);

		$this->em->flush();
	}

	/**
	 * @return Order 
	 */
	public function getOrderByTransaction(Transaction $transaction)
	{
		$criteria = array(
				'transaction' => $transaction->getId()
		);

		$order = $this->orderRepository->findOneBy($criteria);

		if (empty($order)) {
			throw new Exception\RuntimeException('No order for transaction id "' . $transaction->getId() . '"');
		}

		return $order;
	}

	/**
	 * @param User $user 
	 */
	public function getOpenOrderForUser(User $user)
	{
		$criteria = array(
				'userId' => $user->getId(),
				'status' => OrderStatus::OPEN
		);

		$order = $this->orderRepository->findOneBy($criteria);

		if (empty($order)) {

			$order = new Order();
			$order->setUserId($user->getId());
			$this->store($order);
		}

		return $order;
	}

	/**
	 * @param Order $order
	 */
	public function store(Order $order)
	{
		$itemsToRemove = array();

		foreach ($order->getItems() as $orderItem) {
			/* @var $orderItem OrderItem */

			// If this is product item, and quantity ordered is zero, 
			// remove order item as it makes no sense.
			if (
					$orderItem instanceof OrderProductItem &&
					$orderItem->getQuantity() == 0
			) {
				$this->em->remove($orderItem);
				$itemsToRemove[] = $orderItem;
			}
			else {
				$this->em->persist($orderItem);
			}
		}

		foreach ($itemsToRemove as $orderItemToRemove) {
			$order->removeOrderItem($orderItemToRemove);
		}

		$transaction = $order->getTransaction();

		if ( ! empty($transaction)) {
			$this->em->persist($transaction);
		}

		$this->em->persist($order);

		$this->em->flush();
	}

	/**
	 * @return EventManager
	 */
	public function getEventManager()
	{
		return $this->em->getEventManager();
	}

}
