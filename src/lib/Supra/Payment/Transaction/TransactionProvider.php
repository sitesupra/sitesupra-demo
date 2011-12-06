<?php

namespace Supra\Payment\Transaction;

use Supra\Payment\Entity\Transaction\Transaction;
use Supra\Payment\Entity\TransactionParameter;
use Supra\ObjectRepository\ObjectRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Supra\Payment\Provider\PaymentProviderAbstraction;

class TransactionProvider
{

	/**
	 * @var EntityManager
	 */
	protected $em;

	/**
	 * @var EntityRepository
	 */
	protected $transactionRepository;

	function __construct(EntityManager $em = null)
	{
		if (empty($em)) {
			$this->em = ObjectRepository::getEntityManager($this);
		}
		else {
			$this->em = $em;
		}

		$this->transactionRepository = $this->em->getRepository(Transaction::CN());
	}

	/**
	 * @param string $transactionId 
	 */
	public function getTransaction($transactionId)
	{
		$transaction = $this->transactionRepository->find($transactionId);

		if (empty($transaction)) {
			throw new Exception\RuntimeException('Transaction not found for Id "' . $transactionId . '"');
		}

		return $transaction;
	}

	/**
	 * @param Transaction $transaction 
	 */
	public function store(Transaction $transaction)
	{
		foreach ($transaction->getParameters() as $transactionParameter) {
			$this->em->persist($transactionParameter);
		}

		$this->em->persist($transaction);
		$this->em->flush();
	}

	/**
	 * @param Transaction $transaction
	 * @return PaymentProviderAbstraction
	 */
	public function getTransactionPaymentProvider(Transaction $transaction)
	{
		$paymentProviders = ObjectRepository::getPaymentProviderCollection($this);

		$paymentProvider = $paymentProviders->get($transaction->getPaymentProviderId());

		return $paymentProvider;
	}

	/**
	 * @param TransactionParameter $transactionParameter 
	 */
	public function findTransactionsByParameter(TransactionParameter $transactionParameter)
	{
		$query = $this->em->createQuery('SELECT t FROM ' . Transaction::CN() . ' t LEFT JOIN t.parameters tp WHERE '.
				'tp.phaseName = :phaseName AND tp.parameterName = :name AND tp.parameterValue = :value');

		$query->setParameter('phaseName', $transactionParameter->getPhaseName());
		$query->setParameter('name', $transactionParameter->getParameterName());
		$query->setParameter('value', $transactionParameter->getParameterValue());		
		
		$query->execute();

		return $query->getResult();
	}

}

