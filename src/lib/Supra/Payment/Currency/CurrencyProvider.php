<?php

namespace Supra\Payment\Currency;

use Supra\Payment\Entity\Currency\Currency;
use Supra\ObjectRepository\ObjectRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

class CurrencyProvider
{
	/**
	 * @var EntityManager
	 */
	protected $em;

	/**
	 * @var EntityRepository
	 */
	protected $currencyRepository;

	/**
	 * @param EntityManager $em 
	 */
	public function __construct(EntityManager $em = null)
	{
		$this->em = $em ?: ObjectRepository::getEntityManager($this);
	}

	/**
	 * @param string $iso4217Code
	 * @return Currency
	 */
	public function getCurrencyByIso4217Code($iso4217Code)
	{
		$currency = $this->findCurrencyByIso4217Code($iso4217Code);
		
		if ($currency === null) {
			throw new \UnexpectedValueException("Currency with code {$iso4217Code} not found");
		}
		
		return $currency;
	}
	
	/**
	 * @param string $iso4217Code
	 * @return Currency | null
	 */
	public function findCurrencyByIso4217Code($iso4217Code)
	{
		return $this->getRepository()->findOneBy(array('iso4217Code' => $iso4217Code));
	}
		
	/**
	 * @return array
	 */
	public function getAll()
	{
		return $this->getRepository()->findAll();
	}

	/**
	 * @return \Doctrine\ORM\EntityRepository
	 */
	public function getRepository()
	{
		if ($this->repository === null) {
			$this->currencyRepository = $this->em->getRepository(Currency::CN());
		}
		
		return $this->currencyRepository;
	}
}

