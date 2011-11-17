<?php

namespace Supra\Payment\Entity\Currency;

use Supra\Database;

/**
 * @Entity
 */
class Currency extends Database\Entity
{

	/**
	 * @Column(type="string", nullable=false)
	 * @var string
	 */
	protected $isoCode;

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
	 * Returns ISO code for this currency.
	 * @return string
	 */
	public function getIsoCode()
	{
		return $this->isoCode;
	}

}

