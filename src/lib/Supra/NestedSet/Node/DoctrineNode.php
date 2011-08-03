<?php

namespace Supra\NestedSet\Node;

//TODO: remove page entity requirement
use Supra\Controller\Pages\Entity\Abstraction\Entity;
use Supra\NestedSet\DoctrineRepository;
use Supra\NestedSet\RepositoryInterface;
use Supra\NestedSet\Exception;

/**
 * Doctrine database nested set node object
 */
class DoctrineNode extends NodeAbstraction
{
	/**
	 * @var DoctrineRepository
	 */
	protected $repository;

	/**
	 * Pass the doctrine entity the nested set node belongs to
	 * @param Entity $entity
	 */
	public function belongsTo(Entity $entity)
	{
		if ( ! ($entity instanceof NodeInterface)) {
			throw new Exception\WrongInstance($entity, 'Node\NodeInterface');
		}

		$this->left = $entity->getLeftValue();
		$this->right = $entity->getRightValue();
		$this->level = $entity->getLevel();
		$this->title = $entity->__toString();

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

	/**
	 * @param DoctrineRepository $repository
	 * @return DoctrineNode
	 */
	public function setRepository(DoctrineRepository $repository)
	{
		return parent::setRepository($repository);
	}

	/**
	 * @return int
	 * @nestedSetMethod
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

	/**
	 * Prepare object to be processed by garbage collector by removing it's
	 * instance from the Doctrine Repository Array Helper object
	 * @param Entity $entity
	 */
	public function free(Entity $entity)
	{
		$this->repository->free($entity);
		$this->repository = null;
	}

}