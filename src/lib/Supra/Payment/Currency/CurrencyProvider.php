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
	 * @param string $isoCode
	 * @return Currency
	 */
	public function getCurrencyByIsoCode($isoCode)
	{
		$currency = $this->currencyRepository->findOneBy(array('isoCode' => $isoCode));

		if (empty($currency)) {
			
			return $this->createDummyCurrency($isoCode);
			
			//throw new Exception\RuntimeException('Currency not found for ISO code "' . $isoCode . '"');
		}

		return $currency;
	}

	/**
	 * @param string $isoCode 
	 */
	private function createDummyCurrency($isoCode)
	{
		$currency = new Currency();
		$currency->setIsoCode($isoCode);
		$currency->setAbbreviation($isoCode . '-ABBREV');
		$currency->setSymbol($isoCode . '-SYMBOL');
		$currency->setEnabled(true);
		
		$this->em->persist($currency);
		$this->em->flush();
		
		return $currency;
	}

}

