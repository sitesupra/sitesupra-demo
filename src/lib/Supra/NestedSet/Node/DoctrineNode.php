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
	 * @var DoctrineRepository
	 */
	protected $repository;

	public function belongsTo(Entity $entity)
	{
		if ( ! ($entity instanceof NodeInterface)) {
			throw new Exception\WrongInstance($entity, 'Node\NodeInterface');
		}

		$this->left = $entity->getLeftValue();
		$this->right = $entity->getRightValue();
		$this->level = $entity->getLevel();

		$rep = $entity->getRepository();
		if ( ! ($rep instanceof RepositoryInterface)) {
			throw new Exception\WrongInstance($rep, 'RepositoryInterface');
		}
		$nestedSetRepository = $rep->getNestedSetRepository();
		$this->setRepository($nestedSetRepository);
		
		if ($this->right === null) {
			$nestedSetRepository->add($entity);
		}
		$nestedSetRepository->register($entity);
	}

	public function setRepository($repository)
	{
		return parent::setRepository($repository);
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

	public function free(Entity $entity)
	{
		$this->repository->free($entity);
		$this->repository = null;
	}

}