<?php

namespace Supra\Paymeny\Product;

use Supra\ObjectRepository\ObjectRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Supra\Payment\Product\ProductAbstraction;

class ProductProvider
{

	/**
	 * @var EntityManager
	 */
	protected $em;

	function __construct(EntityManager $em = null)
	{

		if ( ! empty($em)) {
			$this->em = $em;
		}
		else {
			$this->em = ObjectRepository::getEntityManager($this);
		}
	}

	/**
	 * Returns entity of product by $id and $className.
	 * @param string $id
	 * @param string $className 
	 * @return ProductAbstraction
	 */
	public function getProductByIdAndClass($id, $className)
	{
		if ( ! class_exists($className)) {
			throw new Exception\RuntimeException('Product class "' . $className . '" not found.');
		}

		$repository = $this->em->getRepository($className);

		$product = $repository->find($id);

		if (empty($product)) {
			throw new Exception\RuntimeException('Product with class "' . $className . '" and ID "' . $id . '" is not found.');
		}

		return $product;
	}

}
