<?php

namespace Supra\Payment\Abstraction;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Supra\Payment\Entity\Abstraction\PaymentEntity;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Payment\Provider\PaymentProviderCollection;
use Supra\Payment\Entity\Abstraction\PaymentEntityParameter;
use Doctrine\ORM\Query;

abstract class PaymentEntityProviderAbstraction
{

	/**
	 * @var EntityManager
	 */
	protected $em;

	/**
	 * @var EntityRepository
	 */
	protected $repository;

	/**
	 * @var PaymentProviderCollection
	 */
	protected $paymentProviderCollection;

	/**
	 * @return string
	 */
	protected abstract function getEntityClassName();

	/**
	 * @return string
	 */
	protected abstract function getEntityParameterClassName();

	/**
	 * @param EntityManager $em 
	 */
	public function setEntityManager(EntityManager $em)
	{
		$this->em = $em;
		$this->repository = null;
	}

	/**
	 * @return EntityManager
	 */
	public function getEntityManager()
	{
		if (empty($this->em)) {
			$this->em = ObjectRepository::getEntityManager($this);
		}

		return $this->em;
	}

	/**
	 * @return EntityRepository
	 */
	public function getRepository()
	{
		if (empty($this->repository)) {

			$em = $this->getEntityManager();

			$this->repository = $em->getRepository($this->getEntityClassName());
		}

		return $this->repository;
	}

	/**
	 * @param PaymentProviderCollection $paymentProviderCollection 
	 */
	public function setPaymentProviderCollection(PaymentProviderCollection $paymentProviderCollection)
	{
		$this->paymentProviderCollection = $paymentProviderCollection;
	}

	/**
	 * @return PaymentProviderCollection
	 */
	public function getPaymentProviderCollection()
	{
		if (empty($this->paymentProviderCollection)) {

			$this->paymentProviderCollection = ObjectRepository::getPaymentProviderCollection($this);
		}

		return $this->paymentProviderCollection;
	}

	/**
	 * @param string $paymentEntityId 
	 * @return PaymentEntity
	 */
	public function getEntiy($paymentEntityId)
	{
		$entity = $this->getRepository()
				->find($paymentEntityId);

		if (empty($entity)) {
			throw new Exception\RuntimeException('Payment entity not found for id "' . $paymentEntityId . '"');
		}

		return $entity;
	}

	/**
	 * @param PaymentEntity $entity 
	 */
	public function store(PaymentEntity $entity, $noFlush = false)
	{
		$em = $this->getEntityManager();

		foreach ($entity->getParameters() as $parameter) {
			$em->persist($parameter);
		}

		$em->persist($entity);

		if ( ! $noFlush) {
			$em->flush();
		}
	}

	/**
	 * @param PaymentEntity $paymentEntity
	 * @return PaymentProviderAbstraction
	 */
	public function getTransactionPaymentProvider(PaymentEntity $paymentEntity)
	{
		$paymentProviderCollection = $this->getPaymentProviderCollection();

		$paymentProvider = $paymentProviderCollection->get($paymentEntity->getPaymentProviderId());

		return $paymentProvider;
	}

	/**
	 *
	 * @param string $phaseName
	 * @param string $name
	 * @param string $value
	 * @return Query;
	 */
	protected function getFindByParameterQuery()
	{
		$dql = 'SELECT t FROM ' . $this->getEntityClassName() . ' t LEFT JOIN t.parameters tp WHERE ' .
				'tp.phaseName = :phaseName AND tp.parameterName = :name AND tp.parameterValue = :value';

		$query = $this->getEntityManager()
				->createQuery($dql);

		return $query;
	}

	/**
	 * @param PaymentEntityParameter $parameter 
	 * @return array
	 */
	public function findByParameter(PaymentEntityParameter $parameter)
	{
		$query = $this->getFindByParameterQuery();

		$query->setParameter('phaseName', $parameter->getPhaseName());
		$query->setParameter('name', $parameter->getParameterName());
		$query->setParameter('value', $parameter->getParameterValue());

		$query->execute();

		return $query->getResult();
	}

}
