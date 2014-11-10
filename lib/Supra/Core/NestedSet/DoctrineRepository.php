<?php

namespace Supra\Core\NestedSet;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\QueryBuilder;

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
	 * Additional condition for all queries
	 * @var string
	 */
	private $additionalCondition;

	/**
	 * SQL version of additional conditions
	 * @var string
	 */
	private $additionalConditionSql;
	
	/**
	 * Query parameter offset
	 * @var int
	 */
	private $parameterOffset = 0;

	private $locked = false;

	/**
	 * Constructor
	 * @param EntityManager $em
	 * @param Mapping\ClassMetadata $class
	 */
	public function __construct(EntityManager $em, Mapping\ClassMetadata $class)
	{
		$this->entityManager = $em;
		$this->className = $class->name;
		$this->arrayHelper = new DoctrineRepositoryArrayHelper($em);
		$platform = $em->getConnection()->getDatabasePlatform();

		$leftField = $class->fieldMappings['left'];

		if (isset($leftField['declared']) && $leftField['declared'] !== $this->className) {
			$class = $em->getClassMetadata($leftField['declared']);
		}

		$this->tableName = $class->getQuotedTableName($platform);
	}

	/**
	 * @param NodeInterface $relativeNode
	 * @throws \RuntimeException
	 */
	public function lock()
	{
		$em = $this->entityManager;

		$tableName = $this->tableName;
		$result = $em->getConnection()->fetchColumn("SELECT GET_LOCK(?, 10)", array($tableName));

		if ($result != 1) {
			throw new Exception\CannotObtainNestedSetLock("Could not lock the nested set $tableName for batch operations");
		}

		$this->locked = true;

		// Do we need to refresh anything?
//		$em->refresh($this->masterNode);
//		$this->refresh();

//		// FIXME: don't know if this might happen..
//		if ($relativeNode instanceof NodeAbstraction) {
//			$relativeNode = $relativeNode->getMasterNode();
//		}
//
//		if ($relativeNode instanceof EntityNodeInterface) {
//			$em->refresh($relativeNode);
//			$relativeNode->getNestedSetNode()->refresh();
//		}
	}

	public function unlock()
	{
		$em = $this->entityManager;
		$tableName = $this->tableName;
		$em->getConnection()->fetchColumn("SELECT RELEASE_LOCK(?)", array($tableName));

		$this->locked = false;
	}

	/**
	 * @return EntityManager
	 */
	public function getEntityManager()
	{
		return $this->entityManager;
	}
	
	/**
	 * @param EntityManager $entityManager 
	 */
	public function setEntityManager($entityManager)
	{
		$this->entityManager = $entityManager;
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
	 * Overrides the class name
	 * @param string $className
	 */
	public function setClassName($className)
	{
		$this->className = $className;
	}
	
	/**
	 * Get quoted table name
	 * @return string
	 */
	public function getTableName()
	{
		return $this->tableName;
	}
	
	/**
	 * Return the current parameter index and increase it
	 * @return int
	 */
	public function increaseParameterOffset()
	{
		return $this->parameterOffset++;
	}

	/**
	 * Get maximal interval value used by nodes
	 * @return int
	 */
	protected function getMax()
	{
		if ( ! $this->locked) {
			//\Log::info('Should lock before changes');
		}

		$dql = "SELECT MAX(e.right) FROM {$this->className} e";
		$dql .= $this->getAdditionalCondition('WHERE');
		$query = $this->entityManager
				->createQuery($dql);
		$max = (int) $query->getSingleScalarResult();

		// Maybe array helper stores even bigger value.
		// In reality it won't happen because items are flushed on DQL run.
		$maxArrayHelper = $this->arrayHelper->getCurrentMax();
		$max = max($max, $maxArrayHelper);

		return $max;
	}

	/**
	 * Remove unused space in the nested set intervals
	 * @param int $offset
	 * @param int $size
	 */
	public function truncate($offset, $size)
	{
		if ( ! $this->locked) {
			//\Log::info('Should lock before changes');
		}

		$size = (int)$size;
		$offset = (int)$offset;

//		foreach (array('left', 'right') as $field) {
		foreach (array('lft', 'rgt') as $field) {
			
			$sql = "UPDATE {$this->tableName}
					SET {$field} = {$field} - {$size}
					WHERE {$field} >= {$offset}";

			// additional condition
			$sql .= $this->getAdditionalConditionSql('AND');

			$this->entityManager->getConnection()->exec($sql);
			
//			$dql = "UPDATE {$this->className} e
//					SET e.{$field} = e.{$field} - {$size}
//					WHERE e.{$field} >= {$offset}";
//
//			$dql .= $this->getAdditionalCondition('AND');
//
//			$query = $this->entityManager->createQuery($dql);
//			$query->execute();
		}

		$this->arrayHelper->truncate($offset, $size);
	}

	/**
	 * Move the node to the new position and change level by {$levelDiff}
	 * @param Node\DoctrineNode $node
	 * @param int $pos
	 * @param int $levelDiff
	 */
	public function move(Node\NodeInterface $node, $pos, $levelDiff, $undoMove = false)
	{
		if ( ! $this->locked) {
			//\Log::info('Should lock before changes');
		}

		$tableName = $this->tableName;
		$arrayHelper = $this->arrayHelper;
		$self = $this;
		
		// Calculate the old position to rollback to in case of some issue
		$oldPosition = $node->getLeftValue();
		
		if ($pos < $oldPosition) {
			$oldPosition = $node->getRightValue() + 1;
		}
		
		if ( ! $node instanceof Node\DoctrineNode) {
			throw new Exception\WrongInstance($node, 'Node\DoctrineNode');
		}
		
		// Transactional because need to rollback in case of trigger failure
		$this->entityManager->transactional(function(EntityManager $entityManager) use ($node, $pos, $levelDiff, $tableName, $arrayHelper, $self) {
			$left = $node->getLeftValue();
			$right = $node->getRightValue();
			$spaceUsed = $right - $left + 1;
			$moveA = null;
			$moveB = null;
			$a = null;
			$b = null;
			$min = null;
			$max = null;

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

//			// NB! It's important to set "level" before "left" for MySQL!
//			$dql = "UPDATE {$className} e
//					SET e.level = e.level + IF(e.left BETWEEN {$left} AND {$right}, {$levelDiff}, 0),
//						e.left = e.left + IF(e.left BETWEEN {$left} AND {$right}, {$moveA}, IF(e.left BETWEEN {$a} AND {$b}, {$moveB}, 0)),
//						e.right = e.right + IF(e.right BETWEEN {$left} AND {$right}, {$moveA}, IF(e.right BETWEEN {$a} AND {$b}, {$moveB}, 0))
//					WHERE (e.left BETWEEN {$min} AND {$max}
//						OR e.right BETWEEN {$min} AND {$max})";
//
//			$dql .= $self->getAdditionalCondition('AND');
//
//			$query = $entityManager->createQuery($dql);
//			$query->execute();

			$sql = "UPDATE {$tableName}
					SET lvl = lvl + IF(lft BETWEEN {$left} AND {$right}, {$levelDiff}, 0),
						lft = lft + IF(lft BETWEEN {$left} AND {$right}, {$moveA}, IF(lft BETWEEN {$a} AND {$b}, {$moveB}, 0)),
						rgt = rgt + IF(rgt BETWEEN {$left} AND {$right}, {$moveA}, IF(rgt BETWEEN {$a} AND {$b}, {$moveB}, 0))
					WHERE (lft BETWEEN {$min} AND {$max}
						OR rgt BETWEEN {$min} AND {$max})";

			$sql .= $self->getAdditionalConditionSql('AND');

			$entityManager->getConnection()
					->exec($sql);
			
			// Change node parameters locally as well
			// TODO: how to rollback these changes if nested set post move trigger fails?
			$arrayHelper->move($node, $pos, $levelDiff);
		});
		
		// Trigger post move event. Only after transaction is commited because
		// public schema must update it's stuff as well
		try {
			$masterNode = $node->getMasterNode();
			$eventArgs = new Event\NestedSetEventArgs($masterNode, $this->entityManager);
			$this->entityManager->getEventManager()
					->dispatchEvent(Event\NestedSetEvents::nestedSetPostMove, $eventArgs);
		} catch (\Exception $e) {
			
			//TODO: new pages should be removed
			
			// Should not happen
			if ($undoMove) {
				throw $e;
			}
			
			// Undo move
			$this->move($node, $oldPosition, - $levelDiff, true);
			
			throw $e;
		}
	}

	/**
	 * Deletes the nested set part under the node including the node
	 * @param Node\NodeInterface $node
	 */
	public function delete(Node\NodeInterface $node)
	{
		if ( ! $this->locked) {
			//\Log::info('Should lock before changes');
		}

		if ( ! $node instanceof Node\DoctrineNode) {
			throw new Exception\WrongInstance($node, 'Node\DoctrineNode');
		}
		
		$left = $node->getLeftValue();
		$right = $node->getRightValue();

		// Deletes only children here because there could be associations that 
		// doesn't allow deletion, then only leafs could be erased
		$sql = "DELETE FROM {$this->tableName}
				WHERE lft > {$left} AND rgt < {$right}";

		$sql .= $this->getAdditionalConditionSql('AND');

		$this->entityManager->getConnection()
				->exec($sql);
		
//		// Deletes only children here because there could be associations that
//		// doesn't allow deletion, then only leafs could be erased
//		$dql = "DELETE FROM {$this->className} e
//				WHERE e.left > {$left} AND e.right < {$right}";
//
//		$dql .= $this->getAdditionalCondition('AND');
//
//		$query = $this->entityManager->createQuery($dql);
//		$query->execute();
		
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
		$qb = $this->createSearchQueryBuilder($filter, $order);

		$result = $qb->getQuery()
				->getResult();
		
		return $result;
	}
	
	/**
	 * @param SearchCondition\SearchConditionInterface $filter
	 * @param SelectOrder\SelectOrderInterface $order
	 * @return QueryBuilder
	 */
	public function createSearchQueryBuilder(SearchCondition\SearchConditionInterface $filter = null, SelectOrder\SelectOrderInterface $order = null)
	{
		if ( ! is_null($filter) && ! $filter instanceof SearchCondition\DoctrineSearchCondition) {
			throw new Exception\WrongInstance($filter, 'SearchCondition\DoctrineSearchCondition');
		}
		
		if ( ! is_null($order) && ! $order instanceof SelectOrder\DoctrineSelectOrder) {
			throw new Exception\WrongInstance($order, 'SelectOrder\DoctrineSelectOrder');
		}
		
		$this->parameterOffset = 0;
		
		$em = $this->getEntityManager();
		$className = $this->className;
		$alias = 'e';
		
		$qb = $em->createQueryBuilder();
		$qb->select($alias)
				->from($className, $alias);

		if (is_null($filter)) {
			$filter = $this->createSearchCondition();
		}
		
		$filter->applyToQueryBuilder($qb, $this->parameterOffset);

		// Default order
		if (is_null($order)) {
			$order = $this->createSelectOrderRule();
			$order->byLeftAscending();
		}
		
		$order->applyToQueryBuilder($qb, $this->parameterOffset);
		
		return $qb;
	}

	/**
	 * Create search condition object
	 * @return SearchCondition\DoctrineSearchCondition
	 */
	public function createSearchCondition()
	{
		$searchCondition = new SearchCondition\DoctrineSearchCondition();
		$searchCondition->setAdditionalCondition($this->getAdditionalCondition());
		
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
		$this->entityManager = null;
	}

	private function getPrefixedPart($part, $prefix = '')
	{
		if ( ! empty($part)) {
			return ' ' . $prefix . ' ' . $part;
		}

		return $part;
	}
	
	/**
	 * Return additional condition with prefix if not empty
	 * @param string $prefix
	 * @return string
	 */
	public function getAdditionalCondition($prefix = '')
	{
		$condition = $this->getPrefixedPart($this->additionalCondition, $prefix);
		
		return $condition;
	}

	/**
	 * Return additional condition with prefix if not empty
	 * @param string $prefix
	 * @return string
	 */
	public function getAdditionalConditionSql($prefix = '')
	{
		$condition = $this->getPrefixedPart($this->additionalConditionSql, $prefix);

		return $condition;
	}

	/**
	 * Sets additional condition (DQL and SQL versions), puts in braces
	 * @param string $additionalCondition
	 * @param string $additionalConditionSql
	 */
	public function setAdditionalCondition($additionalCondition, $additionalConditionSql)
	{
		if ( ! empty($additionalCondition)) {
			$additionalCondition = '(' . $additionalCondition . ')';
		}
		$this->additionalCondition = $additionalCondition;

		if ( ! empty($additionalConditionSql)) {
			$additionalConditionSql = '(' . $additionalConditionSql . ')';
		}
		$this->additionalConditionSql = $additionalConditionSql;
	}

}
