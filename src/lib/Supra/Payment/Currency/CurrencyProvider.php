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
	public function findCurrencyByIsoCode($isoCode)
	{
		$currency = $this->currencyRepositry->findBy(array('isoCode' => $isoCode));

		if (empty($currency)) {
			throw new Exception\RuntimeException('Currency not found for ISO code "' . $isoCode . '"');
		}

		return $currency;
	}

}

