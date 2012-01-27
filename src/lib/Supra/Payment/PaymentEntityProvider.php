<?php

namespace Supra\Payment;

use Supra\Payment\Abstraction\PaymentEntityProviderAbstraction;
use Supra\Payment\Entity\Abstraction\PaymentEntity;
use Supra\Payment\Entity\Abstraction\PaymentEntityParameter;

class PaymentEntityProvider extends PaymentEntityProviderAbstraction
{
	/**
	 * @return string
	 */
	protected function getEntityClassName()
	{
		return PaymentEntity::CN();
	}

	/**
	 *
	 * @return string
	 */
	protected function getEntityParameterClassName()
	{
		return PaymentEntityParameter::CN();
	}

	/**
	 * @param string $phaseName
	 * @param string $name
	 * @param string $value
	 * @return array
	 */
	public function findByParameterPhaseAndNameAndValue($phaseName, $name, $value)
	{
		$query = $this->getFindByParameterQuery();
		
		$query->setParameter('phaseName', $phaseName);
		$query->setParameter('name', $name);
		$query->setParameter('value', $value);

		$query->execute();
		
		return $query->getResult();
	}

}
