<?php

namespace Supra\Payment\Entity;

use Supra\Database;

/**
 * @Entity
 * @Table(indexes={
 * 		@index(name="transactionIdIdx", columns={"transactionId"}),
 * 		@index(name="paymentProviderIdx", columns={"paymentProviderId"})
 * })
 */
class TransactionParameter extends Database\Entity
{

	/**
	 * @Column(type="string", nullable=false)
	 * @var string
	 */
	protected $transactionId;
	
	/**
	 * @Column(type="string", nullable=false)
	 * @var string
	 */
	protected $paymentProviderId;

	/**
	 * @Column(type="string", nullable=false)
	 * @var string
	 */
	protected $parameterName;

	/**
	 * @Column(type="string", nullable=false)
	 * @var string
	 */
	protected $parameteValue;

}

