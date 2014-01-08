<?php

namespace Supra\Search\Mysql;

use Supra\ObjectRepository\ObjectRepository;
use Supra\Search\SearchServiceAdapter;
use Supra\Controller\Pages\Search\PageLocalizationSearchRequest;
use Supra\Controller\Pages\Search\PageLocalizationSearchResultPostProcesser;
use Supra\Search\Result\DefaultSearchResultSet;
use Supra\Search\Mysql\PageLocalizationSearchResultItem;
use Supra\Controller\Pages\Entity\PageLocalization;

class Adapter extends SearchServiceAdapter
{
	/**
	 * MATCH-AGAINST IN NATURAL LANGUAGE MODE (default)
	 */
	const TYPE_DEFAULT = 0;
	
	/**
	 * MATCH-AGAINST IN BOOLEAN MODE
	 */
	const TYPE_BOOLEAN = 1;
	
	/**
	 * MATCH-AGAINST IN NATURAL LANGUAGE MODE WITH QUERY EXPANSION
	 */
	const TYPE_QUERY_EXPANSION = 2;

	protected $defaultMode = Adapter::TYPE_DEFAULT;

	const TABLE_NAME = SEARCH_SERVICE_FULLTEXT_TABLE;

	protected $text;
	protected $startRows = 0;
	protected $maxRows = 10;

	public function configure()
	{
		static $isConfigured = false;

		if ($isConfigured) {
			return true;
		}

		$this->defaultMode = SEARCH_SERVICE_FULLTEXT_DEFAULT_MODE;
		$isConfigured = true;
	}

	/**
	 * @param array $data
	 * @return \Supra\Search\Result\DefaultSearchResultSet
	 */
	public function processResults(array $data)
	{
		$resultSet = new DefaultSearchResultSet();

		foreach ($data as $row) {
			$item = new PageLocalizationSearchResultItem($row, $this->getText());
			
			if ($row['entityClass'] == PageLocalization::CN()) {
				$resultSet->add($item);
			}
		}

		return $resultSet;
	}

	/**
	 * @param Request\SearchRequestInterface $request
	 * @return processResults
	 */
	public function processRequest(\Supra\Search\Request\SearchRequestInterface $request)
	{
		$lm = ObjectRepository::getLocaleManager($this);
		$locale = $lm->getCurrent();

		$sqlParams = array(
			':query' => $this->getText(),
			':locale' => $locale->getId(),
		);

		/** Add asterisk in BOOLEAN MODE */
		if ($this->defaultMode == Adapter::TYPE_BOOLEAN && ! empty($this->text)) {
			$sqlParams[':query'] = $sqlParams[':query'] . '*';
		}

		/** @Object EntityManager */
		$em = ObjectRepository::getEntityManager($this);

		$queryCount = $em->getConnection()->prepare($this->getSqlCount());
		$queryCount->execute($sqlParams);
		$queryCount = $queryCount->fetch();

		/** Prepare SQL Query */
		$sql = $this->getSql();

		$query = $em->getConnection()->prepare($sql);

		$query->execute($sqlParams);

		// Get all data
		$data = $query->fetchAll();

		$resultSet = $this->processResults($data);

		// Total found results
		$resultSet->setTotalResultCount(intval($queryCount['count']));

		return $resultSet;
	}

	/**
	 * @param string $text
	 * @return Supra\Search\DefaultSearchResultSet
	 */
	public function doSearch($text, $maxRows, $startRow)
	{
//		$lm = ObjectRepository::getLocaleManager($this);		
//		$locale = $lm->getCurrent();

		$this->setText($text);
		$this->setMaxRows($maxRows);
		$this->setStartRow($startRow);

		if (empty($text)) {
			$results = new DefaultSearchResultSet();
		} else {
			$searchRequest = new PageLocalizationSearchRequest();
			$results = $this->processRequest($searchRequest);
		}
		
		$pageLocalizationPostProcesser = new PageLocalizationSearchResultPostProcesser();
		$results->addPostprocesser($pageLocalizationPostProcesser);

		$results->runPostprocessers();

		return $results;
	}

	/**
	 * Build SQL Search Query
	 * 
	 * @return string
	 */
	public function getSql()
	{
		$sql = "SELECT * FROM " . Adapter::TABLE_NAME . " WHERE";

		// Where conditions
		$sql .= $this->getSqlWhere();

		// Search in current localization
		$sql .= "ORDER BY updateDate DESC, createDate DESC LIMIT " . intval($this->getStartRow()) . "," . intval($this->getMaxRows());

		return $sql;
	}

	/**
	 * Build SQL Count Search Query
	 * 
	 * @return string
	 */
	public function getSqlCount()
	{
		$sql = "SELECT COUNT(contentId) AS count FROM " . Adapter::TABLE_NAME . " WHERE" . $this->getSqlWhere();

		return $sql;
	}

	/**
	 * Build SQL Where conditions
	 * FULLTEXT INDEX ft1(pageContent,pageTitle)
	 * 
	 * @return string
	 */
	public function getSqlWhere()
	{
		$sql = " ";

		if ($this->defaultMode == Adapter::TYPE_DEFAULT) {
			$sql .= "MATCH (pageContent,pageTitle) AGAINST (:query)";
		} elseif ($this->defaultMode == Adapter::TYPE_BOOLEAN) {
			$sql .= "MATCH (pageContent,pageTitle) AGAINST (:query IN BOOLEAN MODE)";
		} elseif ($this->defaultMode == Adapter::TYPE_QUERY_EXPANSION) {
			$sql .= "MATCH (pageContent,pageTitle) AGAINST (:query IN NATURAL LANGUAGE MODE)";
		}
		// Search in current localization
		$sql .= " AND localeId = :locale ";

		return $sql;
	}

	/**
	 * Set search text
	 * 
	 * @param type $text
	 * @return boolean
	 */
	public function setText($text)
	{
		$this->text = $text;
		return true;
	}

	/**
	 * Get search text
	 * 
	 * @return string
	 */
	public function getText()
	{
		return $this->text;
	}

	/**
	 * Set search max results count
	 * 
	 * @param type $maxRows
	 * @return boolean
	 */
	public function setMaxRows($maxRows)
	{
		$this->maxRows = $maxRows;
		return true;
	}

	/**
	 * Get search max results count
	 * 
	 * @return integer
	 */
	public function getMaxRows()
	{
		return $this->maxRows;
	}

	/**
	 * Set search start position
	 * 
	 * @param type $startRow
	 * @return boolean
	 */
	public function setStartRow($startRow)
	{
		$this->startRow = $startRow;
		return true;
	}

	/**
	 * Get search start position
	 * 
	 * @return type
	 */
	public function getStartRow()
	{
		return $this->startRow;
	}

}