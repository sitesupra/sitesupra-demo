<?php

namespace Supra\Payment\Product;

use Doctrine\ORM\EntityManager;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Payment\Product\ProductAbstraction;

abstract class ProductProviderAbstraction
{

	/**
	 * @var EntityManager
	 */
	protected $entityManager;

	/**
	 * @return EntityManager
	 */
	public function getEntityManager()
	{
		if (empty($this->entityManagerem)) {
			$this->entityManager = ObjectRepository::getEntityManager($this);
		}

		return $this->entityManager;
	}

	/**
	 * @param EntityManager $entityManager 
	 */
	public function setEntityManger(EntityManager $entityManager)
	{
		$this->entityManager = $entityManager;
	}

	/**
	 * @param string $id
	 * @return ProductAbstraction
	 */
	abstract function getById($id);

	/**
	 * @return string
	 */
	public static function CN()
	{
		return get_called_class();
	}

}
