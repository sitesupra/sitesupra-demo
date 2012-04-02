<?php

namespace Supra\Cache;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;

class CachedQueryBuilderWrapper extends QueryBuilder
{
	private $qb;
	
	private $groups;
	
	public function __construct(QueryBuilder $qb, $groups)
	{
		$this->qb = $qb;
		$this->groups = $groups;
	}
	
	/**
	 * Adds cache layer for the query returned
	 * @return Query
	 */
	public function getQuery()
	{
		$query = $this->qb->getQuery();
		
		$cacheGroupManager = new CacheGroupManager();
		$cacheGroupManager->configureQueryResultCache($query, $this->groups);
		
		return $query;
	}

	/**
	 * Return value wrapper to make method chaining work
	 * @param mixed $returnValue
	 * @return mixed
	 */
	public function returnWrapper($returnValue)
	{
		if ($returnValue === $this->qb) {
			return $this;
		}
		
		return $returnValue;
	}
	
	/*
	 * All API methods are overriden to call the wrapped query builder instead of the wrapper
	 */
	
	public function __clone()
	{
		$this->qb = clone($this->qb);
	}

	public function __toString()
	{
		return $this->returnWrapper($this->qb->__toString());
	}

	public function add($dqlPartName, $dqlPart, $append = false)
	{
		return $this->returnWrapper($this->qb->add($dqlPartName, $dqlPart, $append));
	}

	public function addGroupBy($groupBy)
	{
		return $this->returnWrapper($this->qb->addGroupBy($groupBy));
	}

	public function addOrderBy($sort, $order = null)
	{
		return $this->returnWrapper($this->qb->addOrderBy($sort, $order));
	}

	public function addSelect($select = null)
	{
		return $this->returnWrapper($this->qb->addSelect($select));
	}

	public function andHaving($having)
	{
		return $this->returnWrapper($this->qb->andHaving($having));
	}

	public function andWhere($where)
	{
		return $this->returnWrapper($this->qb->andWhere($where));
	}

	public function delete($delete = null, $alias = null)
	{
		return $this->returnWrapper($this->qb->delete($delete, $alias));
	}

	public function distinct($flag = true)
	{
		return $this->returnWrapper($this->qb->distinct($flag));
	}

	public function expr()
	{
		return $this->returnWrapper($this->qb->expr());
	}

	public function from($from, $alias, $indexBy = null)
	{
		return $this->returnWrapper($this->qb->from($from, $alias, $indexBy));
	}

	public function getDQL()
	{
		return $this->returnWrapper($this->qb->getDQL());
	}

	public function getDQLPart($queryPartName)
	{
		return $this->returnWrapper($this->qb->getDQLPart($queryPartName));
	}

	public function getDQLParts()
	{
		return $this->returnWrapper($this->qb->getDQLParts());
	}

	public function getEntityManager()
	{
		return $this->returnWrapper($this->qb->getEntityManager());
	}

	public function getFirstResult()
	{
		return $this->returnWrapper($this->qb->getFirstResult());
	}

	public function getMaxResults()
	{
		return $this->returnWrapper($this->qb->getMaxResults());
	}

	public function getParameter($key)
	{
		$this->qb->getParameter($key);
	}

	public function getParameters()
	{
		return $this->returnWrapper($this->qb->getParameters());
	}

	public function getRootAlias()
	{
		return $this->returnWrapper($this->qb->getRootAlias());
	}

	public function getRootAliases()
	{
		return $this->returnWrapper($this->qb->getRootAliases());
	}

	public function getRootEntities()
	{
		return $this->returnWrapper($this->qb->getRootEntities());
	}

	public function getState()
	{
		return $this->returnWrapper($this->qb->getState());
	}

	public function getType()
	{
		return $this->returnWrapper($this->qb->getType());
	}

	public function groupBy($groupBy)
	{
		return $this->returnWrapper($this->qb->groupBy($groupBy));
	}

	public function having($having)
	{
		return $this->returnWrapper($this->qb->having($having));
	}

	public function innerJoin($join, $alias, $conditionType = null, $condition = null, $indexBy = null)
	{
		return $this->returnWrapper($this->qb->innerJoin($join, $alias, $conditionType, $condition, $indexBy));
	}

	public function join($join, $alias, $conditionType = null, $condition = null, $indexBy = null)
	{
		return $this->returnWrapper($this->qb->join($join, $alias, $conditionType, $condition, $indexBy));
	}

	public function leftJoin($join, $alias, $conditionType = null, $condition = null, $indexBy = null)
	{
		return $this->returnWrapper($this->qb->leftJoin($join, $alias, $conditionType, $condition, $indexBy));
	}

	public function orHaving($having)
	{
		return $this->returnWrapper($this->qb->orHaving($having));
	}

	public function orWhere($where)
	{
		return $this->returnWrapper($this->qb->orWhere($where));
	}

	public function orderBy($sort, $order = null)
	{
		return $this->returnWrapper($this->qb->orderBy($sort, $order));
	}

	public function resetDQLPart($part)
	{
		return $this->returnWrapper($this->qb->resetDQLPart($part));
	}

	public function resetDQLParts($parts = null)
	{
		return $this->returnWrapper($this->qb->resetDQLParts($parts));
	}

	public function select($select = null)
	{
		return $this->returnWrapper($this->qb->select($select));
	}

	public function set($key, $value)
	{
		return $this->returnWrapper($this->qb->set($key, $value));
	}

	public function setFirstResult($firstResult)
	{
		return $this->returnWrapper($this->qb->setFirstResult($firstResult));
	}

	public function setMaxResults($maxResults)
	{
		return $this->returnWrapper($this->qb->setMaxResults($maxResults));
	}

	public function setParameter($key, $value, $type = null)
	{
		return $this->returnWrapper($this->qb->setParameter($key, $value, $type));
	}

	public function setParameters(array $params, array $types = array())
	{
		return $this->returnWrapper($this->qb->setParameters($params, $types));
	}

	public function update($update = null, $alias = null)
	{
		return $this->returnWrapper($this->qb->update($update, $alias));
	}

	public function where($predicates)
	{
		return $this->returnWrapper($this->qb->where($predicates));
	}

}
