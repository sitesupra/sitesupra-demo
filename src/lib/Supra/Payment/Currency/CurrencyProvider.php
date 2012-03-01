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
	function __construct(EntityManager $em = null)
	{
		if ( ! empty($em)) {
			$this->em = $em;
		}
		else {
			$this->em = ObjectRepository::getEntityManager($this);
		}

		$this->currencyRepository = $this->em->getRepository(Currency::CN());
	}

	/**
	 * @param string $iso4217Code
	 * @return Currency
	 */
	public function getCurrencyByIso4217Code($iso4217Code)
	{
		$currency = $this->currencyRepository->findOneBy(array('iso4217Code' => $iso4217Code));

		if (empty($currency)) {
			
			return $this->createDummyCurrency($iso4217Code);
			
			//throw new Exception\RuntimeException('Currency not found for ISO code "' . $isoCode . '"');
		}

		return $currency;
	}
	

	/**
	 * @param string $iso4217Code 
	 */
	private function createDummyCurrency($iso4217Code)
	{
		$currency = new Currency();
		$currency->setIso4217Code($iso4217Code);
		$currency->setAbbreviation($iso4217Code . '-ABBREV');
		$currency->setSymbol($iso4217Code . '-SYMBOL');
		$currency->setEnabled(true);
		
		$this->em->persist($currency);
		$this->em->flush();
		
		return $currency;
	}

}

