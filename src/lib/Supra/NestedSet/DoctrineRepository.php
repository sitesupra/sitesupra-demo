<?php

namespace Supra\NestedSet;

use Closure,
		Doctrine\ORM\EntityManager,
		Doctrine\ORM\Mapping,
		Doctrine\ORM\QueryBuilder,
		Supra\Controller\Pages\Entity\Abstraction\Entity,
		Node\NodeInterface;

/**
 * 
 */
class DoctrineRepository extends RepositoryAbstraction
{
	/**
	 * Loaded object repository
	 * @var DoctrineRepositoryArrayHelper
	 */
	protected $arrayHelper;

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
	 * @var string
	 */
	protected $tableName;

	/**
	 * @var int
	 */
	protected $max = 0;

	/**
	 * Constructor
	 * @param EntityManager $em
	 * @param Mapping\ClassMetadata $class
	 */
	public function __construct(EntityManager $em, Mapping\ClassMetadata $class)
	{
		$this->entityManager = $em;
		$this->classMetadata = $class;
		$this->className = $class->name;
		$this->arrayHelper = new DoctrineRepositoryArrayHelper();
		$platform = $em->getConnection()->getDatabasePlatform();
		$this->tableName = $class->getQuotedTableName($platform);
	}

	/**
	 * @return EntityManager
	 */
	public function getEntityManager()
	{
		return $this->entityManager;
	}

	/**
	 * Get class name of managed Doctrine entity
	 * @return string
	 */
	public function getClassName()
	{
		return $this->className;
	}

	/**
	 * Get maximal interval value used by nodes
	 * @return int
	 */
	protected function getMax()
	{
		$dql = "SELECT MAX(e.right) FROM {$this->className} e";
		$query = $this->entityManager
				->createQuery($dql);
		$max = (int)$query->getSingleScalarResult();

		// Maybe array helper stores even bigger value.
		// In reality it won't happen because items are flushed on DQL run.
		$maxArrayHelper = $this->arrayHelper->getCurrentMax();
		$max = max($max, $maxArrayHelper);

		return $max;
	}

//	public function extend($offset, $size)
//	{
//		$size = (int)$size;
//		$offset = (int)$offset;
//
//		foreach (array('left', 'right') as $field) {
//			$dql = "UPDATE {$this->className} e
//					SET e.{$field} = e.{$field} + ?2
//					WHERE e.{$field} >= ?1";
//
//			$query = $this->entityManager->createQuery($dql);
//			$query->execute(array(1 => $offset, 2 => $size));
//		}
//
//		$this->arrayHelper->extend($offset, $size);
//	}

	/**
	 * Remove unused space in the nested set intervals
	 * @param int $offset
	 * @param int $size
	 */
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

	/**
	 * Move the node to the new position and change level by {$levelDiff}
	 * @param Node\DoctrineNode $node
	 * @param int $pos
	 * @param int $levelDiff
	 */
	public function move(Node\DoctrineNode $node, $pos, $levelDiff)
	{
		// flush before update
		$this->entityManager->flush();

		$left = $node->getLeftValue();
		$right = $node->getRightValue();
		$spaceUsed = $right - $left + 1;
		if ($pos > $left) {
			$a = $right + 1;
			$b = $pos - 1;
			$moveA = $pos - $left - $spaceUsed;
			$moveB = - $spaceUsed;
			$min = $left;
			$max = $pos - 1;
		} else {
			$a = $pos;
			$b = $left - 1;
			$moveA = $pos - $left;
			$moveB = $spaceUsed;
			$min = $pos;
			$max = $right;
		}

		// Using SQL because DQL does not support such format
		// Will fail with SQL server implementation without function IF(cond, yes, no)
		// NB! It's important to set "lvl" as first for MySQL
		$sql = "UPDATE {$this->tableName}
				SET lvl = lvl + IF(lft BETWEEN {$left} AND {$right}, {$levelDiff}, 0),
					lft = lft + IF(lft BETWEEN {$left} AND {$right}, {$moveA}, IF(lft BETWEEN {$a} AND {$b}, {$moveB}, 0)),
					rgt = rgt + IF(rgt BETWEEN {$left} AND {$right}, {$moveA}, IF(rgt BETWEEN {$a} AND {$b}, {$moveB}, 0))
				WHERE lft BETWEEN {$min} AND {$max}
					OR rgt BETWEEN {$min} AND {$max}";

		//TODO: trigger some stuff...
		//$this->

		$connection = $this->entityManager->getConnection();
		$statement = $connection->prepare($sql);
		$result = $statement->execute();
		// Throw the exception if the exceptions are not thrown by the statement
		if ( ! $result) {
			$errorInfo = $statement->errorInfo();
			$errorString = $errorInfo[2];
			throw new \PDOException($errorString);
		}

		$this->arrayHelper->move($node, $pos, $levelDiff);
		
	}

//	public function oldMove(Node\DoctrineNode $node, $pos, $levelDiff = 0)
//	{
//		$pos = (int)$pos;
//		$levelDiff = (int)$levelDiff;
//
//		$left = $node->getLeftValue();
//		$right = $node->getRightValue();
//		$diff = $pos - $left;
//
//		$dql = "UPDATE {$this->className} e
//				SET e.left = e.left + {$diff},
//					e.right = e.right + {$diff},
//					e.level = e.level + {$levelDiff}
//				WHERE e.left >= {$left} AND e.right <= {$right}";
//
//		$query = $this->entityManager->createQuery($dql);
//		$query->execute();
//
//		$this->arrayHelper->move($node, $pos, $levelDiff);
//	}

	/**
	 * Deletes the nested set part under the node including the node
	 * @param Node\DoctrineNode $node
	 */
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

	/**
	 * Perform the search in the database
	 * @param SearchCondition\SearchConditionInterface $filter
	 * @param SelectOrder\SelectOrderInterface $order
	 * @return array
	 */
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

	/**
	 * Create search condition object
	 * @return SearchCondition\DoctrineSearchCondition
	 */
	public function createSearchCondition()
	{
		$searchCondition = new SearchCondition\DoctrineSearchCondition();
		return $searchCondition;
	}

	/**
	 * Create order rule object
	 * @return SelectOrder\DoctrineSelectOrder
	 */
	public function createSelectOrderRule()
	{
		$SelectOrder = new SelectOrder\DoctrineSelectOrder();
		return $SelectOrder;
	}

	/**
	 * Register the node
	 * @param Node\NodeInterface $node
	 */
	public function register(Node\NodeInterface $node)
	{
		$this->arrayHelper->register($node);
	}

	/**
	 * Free the node
	 * @param Node\NodeInterface $node
	 */
	public function free(Node\NodeInterface $node = null)
	{
		if (is_null($node)) {
			$this->arrayHelper->free();
		} else {
			$this->arrayHelper->free($node);
		}
	}

	/**
	 * Prepare object for garbage collector
	 */
	public function destroy()
	{
		$this->arrayHelper->destroy();
		$this->arrayHelper = null;
		$this->classMetadata = null;
		$this->entityManager = null;
	}
}