<?php

namespace Supra\Payment\Order;

use Supra\ObjectRepository\ObjectRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Supra\Payment\Entity\Order\Order;
use Supra\Payment\Entity\Order\OrderItem;
use Supra\Paymeny\Product\ProductProvider;
use Supra\Payment\Provider\PaymentProviderAbstraction;

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
			$amount = $orderItem->getAmount();

			$product = $productProvider->getProductByIdAndClass($productId, $productClass);

			$newPrices[$orderItem->getId()] = $product->getPrice($amount, $newCurrency);
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

	public function prepareOrderForPayment(Order $order, PaymentProviderAbstraction $paymentProvider, $paymentProviderAccount)
	{
		$transaction = $paymentProvider->createTransaction();
		$transaction->setPaymentProviderAccount($paymentProviderAccount);

		$orderUser = $order->getUser();
		$transaction->setUser($orderUser);

		$order->setTransaction($transaction);
		$order->setStatus(OrderStatus::PAYMENT_STARTED);

		$this->em->persist($order);
		$this->em->persist($transaction);

		$this->em->flush();
	}

}
