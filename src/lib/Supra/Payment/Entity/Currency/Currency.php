<?php

namespace Supra\Payment\Entity\Currency;

use Supra\Database;

/**
 * @Entity
 * @InheritanceType("SINGLE_TABLE")
 * @DetachedDiscriminators
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({"base" = "Currency"})
 */
class Currency extends Database\Entity
{
	/**
	 * @Column(type="string", nullable=false)
	 * @var string
	 */
	protected $iso4217Code;

	/**
	 * @Column(type="string", nullable=false)
	 * @var string
	 */
	protected $abbreviation;

	/**
	 * @Column(type="string", nullable=false)
	 * @var string
	 */
	protected $symbol;

	/**
	 * @Column(type="boolean", nullable=false)
	 * @var boolean
	 */
	protected $enabled;

	/**
	 * Returns ISO-4217 code for this currency.
	 * @return string
	 */
	public function getIso4217Code()
	{
		return $this->iso4217Code;
	}

	public function setIso4217Code($iso4217Code)
	{
		$this->iso4217Code = $iso4217Code;
	}

	public function setAbbreviation($abbreviation)
	{
		$this->abbreviation = $abbreviation;
	}

	public function setSymbol($symbol)
	{
		$this->symbol = $symbol;
	}

	public function getSymbol()
	{
		return $this->symbol;
	}
	
	public function setEnabled($enabled)
	{
		$this->enabled = $enabled;
	}
}
