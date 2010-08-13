<?php

namespace Supra\NestedSet\Node;

use Supra\Controller\Pages\Entity\Abstraction\Entity,
		Supra\NestedSet\DoctrineRepository,
		Supra\NestedSet\RepositoryInterface,
		Supra\NestedSet\Exception;

/**
 * 
 */
class DoctrineNode extends NodeAbstraction
{
	/**
	 * @var Entity
	 */
	protected $entity;

	/**
	 * @var DoctrineRepository
	 */
	protected $repository;

	public function  __construct(Entity $entity)
	{
		$this->entity = $entity;
		$rep = $entity->getRepository();
		if ( ! ($rep instanceof RepositoryInterface)) {
			throw new Exception\WrongInstance($rep, 'RepositoryInterface');
		}
		$nestedSetRepository = $rep->getNestedSetRepository();
		$this->setRepository($nestedSetRepository);
		if ($this->getRightValue() === null) {
			$nestedSetRepository->add($this);
		}
		$nestedSetRepository->register($this);
	}

	public function setRepository(DoctrineRepository $repository)
	{
		return parent::setRepository($repository);
	}

	public function getLeftValue()
	{
		return $this->entity->getLeftValue();
	}

	public function getLevel()
	{
		return $this->entity->getLevel();
	}

	public function getRightValue()
	{
		return $this->entity->getRightValue();
	}

	public function setLeftValue($left)
	{
		return $this->entity->setLeftValue($left);
	}

	public function setLevel($level)
	{
		return $this->entity->setLevel($level);
	}

	public function setRightValue($right)
	{
		return $this->entity->setRightValue($right);
	}

	/**
	 * @return int
	 */
	public function getNumberChildren()
	{
		/*
		 * It's cheaper to call descendant number count.
		 * If the count is less than 2 they are all children
		 */
		$descendantNumber = $this->getNumberDescendants();
		if ($descendantNumber <= 1) {
			return $descendantNumber;
		}

		$rep = $this->repository;

		$search = $rep->createSearchCondition()
				->leftMoreThan($this->getLeftValue())
				->rightLessThan($this->getRightValue())
				->levelEqualsTo($this->getLevel() + 1);

		$em = $rep->getEntityManager();
		$className = $rep->getClassName();
		$qb = $em->createQueryBuilder();
		$qb->select('COUNT(e.id)')
				->from($className, 'e');

		$search->getSearchDQL($qb);

		$count = $qb->getQuery()->getSingleScalarResult();
		return $count;
	}

	public function free()
	{
		$this->repository->free($this);
		$this->entity = null;
		$this->repository = null;
	}

}