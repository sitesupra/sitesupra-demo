<?php

namespace Supra\Payment\Entity;

use Supra\Database;
use Supra\Payment\Entity\Transaction\Transaction;

/**
 * @Entity
 * @Table(indexes={
 * 		@index(name="transactionIdIdx", columns={"transactionId"}),
 * 		@index(name="phaseNameIdx", columns={"phaseName"})
 * })
 */
class TransactionParameter extends Database\Entity
{

	/**
	 * @ManyToOne(targetEntity="Supra\Payment\Entity\Transaction\Transaction", inversedBy="parameters")
	 * @JoinColumn(name="transactionId", referencedColumnName="id")
	 * @var Transaction
	 */
	protected $transaction;

	/**
	 * @Column(type="string", nullable=false)
	 * @var string
	 */
	protected $phaseName = null;

	/**
	 * @Column(type="string", nullable=false)
	 * @var string
	 */
	protected $parameterName = null;

	/**
	 * @Column(type="string", nullable=false)
	 * @var string
	 */
	protected $parameterValue = null;

	/**
	 * @param string $phaseName
	 * @param string $name
	 * @param string $value 
	 */
	function __construct($phaseName = null, $name = null, $value = null)
	{
		parent::__construct();

		$this->phaseName = $phaseName;
		$this->parameterName = $name;
		$this->parameterValue = $value;
	}

	/**
	 * @param string $name 
	 */
	public function setName($name)
	{
		$this->parameterName = $name;
	}

	/**
	 * @param string $value 
	 */
	public function setValue($value)
	{
		$this->parameterValue = $value;
	}

	/**
	 * @param Transaction $transaction 
	 */
	public function setTransaction(Transaction $transaction)
	{
		$this->transaction = $transaction;
	}

	/**
	 * @param string $phaseName 
	 */
	public function setPhaseName($phaseName)
	{
		$this->phaseName = $phaseName;
	}

	/**
	 * @return type 
	 */
	public function getPhaseName()
	{
		return $this->phaseName;
	}

	/**
	 * @return string
	 */
	public function getParameterName()
	{
		return $this->parameterName;
	}

	/**
	 * @return string
	 */
	public function getParameterValue()
	{
		return $this->parameterValue;
	}

}

