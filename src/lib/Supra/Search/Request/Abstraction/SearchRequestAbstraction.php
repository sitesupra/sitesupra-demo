<?php

namespace Supra\Search\Request\Abstraction;

use \Solarium_Query_Select;
use Supra\Search\Request\SearchRequestInterface;

abstract class SearchRequestAbstraction implements SearchRequestInterface
{
	const ORDER_ASC = Solarium_Query_Select::SORT_ASC;
	const ORDER_DESC = Solarium_Query_Select::SORT_DESC;
	const ORDER_NONE = null;

	protected $highlightedFields;
	protected $highlightPrefix;
	protected $highlightPostfix;
	protected $sortField;
	protected $sortOrder;
	protected $resultStartRow = null;
	protected $resultMaxRows = null;
	protected $simpleFilters = array();

	/**
	 * Applies all collected parameters to Solarium select query.
	 * @param Solarium_Query_Select $selectQuery 
	 */
	public function applyParametersToSelectQuery(Solarium_Query_Select $selectQuery)
	{
		$this->applyHilightingOptions($selectQuery);
		$this->applyResultPartitioning($selectQuery);
		$this->applySortFieldAndOrder($selectQuery);
		$this->applyFilters($selectQuery);
	}

	/**
	 * Sets starging row of resultset fragment.
	 * @param integer $resultStartRow 
	 */
	public function setResultStartow($resultStartRow)
	{
		$this->resultStartRow = $resultStartRow;
	}

	/**
	 * Sets number of that will be returned from whole result.
	 * @param integer $resultMaxRows 
	 */
	public function setResultMaxRows($resultMaxRows)
	{
		$this->resultMaxRows = $resultMaxRows;
	}

	/**
	 * Applies result partitioning (starting row and row count) to $selectQuery.
	 * @param Solarium_Query_Select $selectQuery 
	 */
	protected function applyResultPartitioning(Solarium_Query_Select $selectQuery)
	{
		if ( ! empty($this->resultMaxRows)) {
			$selectQuery->setRows($this->resultMaxRows);
		}

		if ( ! empty($this->resultStartRow)) {
			$selectQuery->setSorts($this->resultStartRow);
		}
	}

	/**
	 * Sets highlighting options - fileds highlighted, prefix and suffix of highlighted fragment.
	 * @param array $fields
	 * @param string $prefix
	 * @param string $postfix 
	 */
	public function setHilightingOptions($fields, $prefix, $postfix)
	{
		$this->highlightedFields = $fields;
		$this->highlightPrefix = $prefix;
		$this->highlightPostfix = $postfix;
	}

	/**
	 * Applies highlighting options to $selectQuery.
	 * @param Solarium_Query_Select $selectQuery 
	 */
	protected function applyHilightingOptions(Solarium_Query_Select $selectQuery)
	{
		if ( ! empty($this->highlightedFields)) {

			$hl = $selectQuery->getHighlighting();

			$hl->setFields($this->highlightedFields);
			$hl->setSimplePrefix($this->highlightPrefix);
			$hl->setSimplePostfix($this->highlightPostfix);
		}
	}

	/**
	 * Sets sorting field.
	 * @param string $field
	 * @param string $order 
	 */
	public function setSortFieldAndOrder($field, $order = self::ORDER_ASC)
	{
		$this->sortField = $field;
		$this->sortOrder = $order;
	}

	/**
	 * A shorthand to set ordering by score.
	 * @param string $order 
	 */
	public function setSortByScore($order = self::ORDER_ASC) 
	{
		$this->setSortFieldAndOrder('score', $order);
	}
	
	/**
	 * Sets sorting order. Use SORT_* constants from SearchRequestAbstraction class.
	 * @param Solarium_Query_Select $selectQuery 
	 */
	protected function applySortFieldAndOrder(Solarium_Query_Select $selectQuery)
	{
		if ( ! empty($this->sortField)) {
			$selectQuery->addSort($this->sortField, $this->sortOrder);
		}
	}

	/**
	 * Adds simple filter definition to filter list.
	 * @param type $name
	 * @param type $value 
	 */
	public function addSimpleFilter($name, $value)
	{
		$this->simpleFilters[$name] = $value;
	}

	/**
	 * Applies (adds) filter queries to select query.
	 * @return array
	 */
	protected function applyFilters(Solarium_Query_Select $selectQuery)
	{
		foreach ($this->simpleFilters as $name => $value) {
			$this->addSimpleFilterToSelectQuery($selectQuery, $name, $value);
		}
	}

	/**
	 * @param Solarium_Query_Select $selectQuery
	 * @param string $name
	 * @param string $value 
	 */
	protected function addSimpleFilterToSelectQuery($selectQuery, $name, $value)
	{
		$qh = $selectQuery->getHelper();
		$fq = $selectQuery->createFilterQuery();

		$fq->setKey($name);
		$fq->setQuery($name . ':' . $qh->escapePhrase($value));

		$selectQuery->addFilterQuery($fq);
	}

}

