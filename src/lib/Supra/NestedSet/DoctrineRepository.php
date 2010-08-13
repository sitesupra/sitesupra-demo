<?php

namespace Supra\NestedSet;

use Closure,
		Doctrine\ORM\EntityManager,
		Doctrine\ORM\Mapping,
		Doctrine\ORM\QueryBuilder,
		Supra\Controller\Pages\Entity\Abstraction\Entity;

/**
 * 
 */
class DoctrineRepository extends RepositoryAbstraction
{
	protected $array = array();

	/**
	 * @var EntityManager
	 */
	protected $entityManager;

	/**
	 * @var Mapping\ClassMetadata
	 */
	protected $classMetadata;

	/**
	 * @var string
	 */
	protected $className;

	/**
	 * @var int
	 */
	protected $max = 0;

	public function  __construct(EntityManager $em, Mapping\ClassMetadata $class)
	{
		$this->entityManager = $em;
		$this->classMetadata = $class;
		$this->className = $class->name;
		$this->arrayHelper = new DoctrineRepositoryArrayHelper();
	}

	/**
	 * @return EntityManager
	 */
	public function getEntityManager()
	{
		return $this->entityManager;
	}

	public function getClassName()
	{
		return $this->className;
	}

	protected function getMax()
	{
		$dql = "SELECT MAX(e.right) FROM {$this->className} e";
		$query = $this->entityManager
				->createQuery($dql);
		$max = (int)$query->getSingleScalarResult();

		// locally keep the MAX value in case when something is not flushed
		if ($max < $this->max) {
			$max = $this->max;
		}
		$this->max = $max + 2;
		return $max;
	}

	public function createNode(Entity $entity)
	{
		$node = new Node\DoctrineNode($entity);
		$entity->setNestedSetNode($node);

		$this->add($node);

		// Must persist after setting nested set intervals because query is run there
		//$this->entityManager->persist($entity);
		return $node;
	}

	public function extend($offset, $size)
	{
		$size = (int)$size;
		$offset = (int)$offset;

		foreach (array('left', 'right') as $field) {
			$dql = "UPDATE {$this->className} e
					SET e.{$field} = e.{$field} + {$size}
					WHERE e.{$field} >= {$offset}";

			$query = $this->entityManager->createQuery($dql);
			$query->execute();
		}

		$this->arrayHelper->extend($offset, $size);
	}

	public function truncate($offset, $size)
	{
		$size = (int)$size;
		$offset = (int)$offset;

		foreach (array('left', 'right') as $field) {
			$dql = "UPDATE {$this->className} e
					SET e.{$field} = e.{$field} - {$size}
					WHERE e.{$field} >= {$offset}";

			$query = $this->entityManager->createQuery($dql);
			$query->execute();
		}

		$this->arrayHelper->truncate($offset, $size);
	}

	public function move(Node\DoctrineNode $node, $pos, $levelDiff = 0)
	{
		$pos = (int)$pos;
		$levelDiff = (int)$levelDiff;
		
		$left = $node->getLeftValue();
		$right = $node->getRightValue();
		$diff = $pos - $left;

		$dql = "UPDATE {$this->className} e
				SET e.left = e.left + {$diff},
					e.right = e.right + {$diff},
					e.level = e.level + {$levelDiff}
				WHERE e.left >= {$left} AND e.right <= {$right}";

		$query = $this->entityManager->createQuery($dql);
		$query->execute();

		$this->arrayHelper->move($node, $pos, $levelDiff);
	}

	public function delete(Node\DoctrineNode $node)
	{
		$left = $node->getLeftValue();
		$right = $node->getRightValue();

		$dql = "DELETE FROM {$this->className} e
				WHERE e.left >= {$left} AND e.right <= {$right}";

		$query = $this->entityManager->createQuery($dql);
		$query->execute();
		
		$this->arrayHelper->delete($node);
	}

	public function search(SearchCondition\SearchConditionInterface $filter, SelectOrder\SelectOrderInterface $order = null)
	{
		$em = $this->getEntityManager();
		$className = $this->className;

		$qb = $em->createQueryBuilder();
		$qb->select('e')
				->from($className, 'e');

		if ( ! ($filter instanceof SearchCondition\DoctrineSearchCondition)) {
			throw new Exception\WrongInstance($filter, 'SearchCondition\DoctrineSearchCondition');
		}
		$qb = $filter->getSearchDQL($qb);

		if ( ! \is_null($order)) {
			if ( ! ($order instanceof SelectOrder\DoctrineSelectOrder)) {
				throw new Exception\WrongInstance($order, 'SelectOrder\DoctrineSelectOrder');
			}
			$qb = $order->getOrderDQL($qb);
		}

		$result = $qb->getQuery()
				->getResult();
		return $result;
	}

	public function createSearchCondition()
	{
		$searchCondition = new SearchCondition\DoctrineSearchCondition();
		return $searchCondition;
	}

	public function createSelectOrderRule()
	{
		$SelectOrder = new SelectOrder\DoctrineSelectOrder();
		return $SelectOrder;
	}

	/**
	 * Must be called after update/delete action
	 */
	public function reloadNodesFromDatabase()
	{
		throw new Exception\NotImplemented(__METHOD__);
	}

	public function register(Node\DoctrineNode $node)
	{
		$this->arrayHelper->register($node);
	}

	public function free(Node\DoctrineNode $node)
	{
		$this->arrayHelper->free($node);
	}

	public function destroy()
	{
		$this->arrayHelper = null;
		$this->classMetadata = null;
		$this->entityManager = null;
	}
}