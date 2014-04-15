<?php

namespace Supra\Search\Mysql;

use Supra\ObjectRepository\ObjectRepository;
use Supra\Search\Result\DefaultSearchResultSet;
use Supra\Search\Mysql\PageLocalizationSearchResultItem;
use Supra\Controller\Pages\Entity\PageLocalization;
use Supra\Search\Request\SearchRequestInterface;
use Supra\Controller\Pages\Search\PageLocalizationSearchRequest;

class MysqlSearcher extends \Supra\Search\SearcherAbstract
{
	/**
	 * IN NATURAL LANGUAGE MODE
	 */
	const TYPE_IN_NATURAL_LANGUAGE = 0;
	
	/**
	 * IN BOOLEAN MODE
	 */
	const TYPE_BOOLEAN = 1;
	
	/**
	 * WITH QUERY EXPANSION
	 */
	const TYPE_WITH_QUERY_EXPANSION = 2;
		
	/**
	 */
	const MIN_WORD_LENGTH = 3;

	/**
	 * @var int
	 */
	protected $type = self::TYPE_IN_NATURAL_LANGUAGE;
		
	/**
	 * Contents table
	 * @var string
	 */
	protected $indexedContentTableName = 'search_indexed_content';

	/**
	 * @param string $name
	 */
	public function setIndexedContentTableName($name)
	{
		$this->indexedContentTableName = $name;
	}
	
	/**
	 * @return string
	 */
	public function getIndexedContentTableName()
	{
		return $this->indexedContentTableName;
	}
	
	/**
	 * Set the fulltext search type
	 * See http://dev.mysql.com/doc/refman/5.5/en/fulltext-search.html for more info
	 * 
	 * @param integer $type
	 * @throws \InvalidArgumentException
	 */
	public function setSearchType($type)
	{
		if ( ! in_array($type, array(static::SEARCH_TYPE_NATURAL_LANGUAGE_MODE,
				static::SEARCH_TYPE_BOOLEAN_MODE,
				static::SEARCH_TYPE_WITH_QUERY_EXPANSION))) {
			
			throw new \InvalidArgumentException('Provided type is unknown');
		}
		
		$this->searchType = $type;
	}

	/**
	 * @param Request\SearchRequestInterface $request
	 * @return processResults
	 */
	public function processRequest(SearchRequestInterface $request)
	{
		/* @var $request PageLocalizationSearchRequest */
		$em = ObjectRepository::getEntityManager($this);
		$connection = $em->getConnection();
		
		if ( ! $connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MySqlPlatform) {
			throw new \RuntimeException('MySQL search adapter requires connection with MySQL database');
		}
		
		if ( ! $request instanceof PageLocalizationSearchRequest) {
			return new DefaultSearchResultSet;
		}
		
		if (mb_strlen($request->getText()) < self::MIN_WORD_LENGTH) {
			return new DefaultSearchResultSet;
		}
		
		$statement = $connection->prepare($this->getCountQuery());
		
		$this->bindSearchValues($statement, $request);
		
		$statement->execute();
		
		$count = (int) $statement->fetchColumn();
		
		// Makes sense to stop, if count was 0
		if ($count === 0) {
			
			$resultSet = new DefaultSearchResultSet;
			$resultSet->setTotalResultCount(0);
			
			return $resultSet;
		}
		
		$statement = $connection->prepare($this->createSearchQueryForRequest($request));
		
		$this->bindSearchValues($statement, $request);
		
		$statement->execute();
		
		$resultSet = $this->processResults($statement->fetchAll(), $request);
		$resultSet->setTotalResultCount($count);

		return $resultSet;
	}
	
	/**
	 * @param array $data
	 * @return \Supra\Search\Result\DefaultSearchResultSet
	 */
	protected function processResults(array $results, $request)
	{
		$requestQuery = $request->getText();
		
		$set = new DefaultSearchResultSet();

		foreach ($results as $row) {
			
			if ($row['entityClass'] == PageLocalization::CN()) {
				$item = new PageLocalizationSearchResultItem($row, $requestQuery);
				$set->add($item);
			}
		}

		return $set;
	}

	protected function createSearchQueryForRequest($request)
	{	
		/* @var $request PageLocalizationSearchRequest */
		
		$table = $this->getIndexedContentTableName();
		$query = $this->addWhereCondition("SELECT t.* FROM {$table} t");
		
		$query .= ' ORDER BY t.updated DESC, t.created DESC';

		$startRow = (int) $request->getResultStartRow();
		$maxRows = (int) $request->getResultMaxRows();
		
		$query .= " LIMIT {$startRow}, {$maxRows}";
		
		return $query;
	}

	/**
	 * @return string
	 */
	protected function getCountQuery()
	{
		$table = $this->getIndexedContentTableName();
		return $this->addWhereCondition("SELECT COUNT(t.id) AS count FROM {$table} t");
	}
		
	/**
	 * @param string $query
	 * @return string
	 */
	protected function addWhereCondition($query)
	{
		$query .= ' WHERE t.locale = :locale AND MATCH (t.content, t.title) ';
		
		switch ($this->type) {
			case static::TYPE_IN_NATURAL_LANGUAGE:
				$query .= 'AGAINST (:query IN NATURAL LANGUAGE MODE)';
				break;
			
			case static::TYPE_BOOLEAN:
				$query .= 'AGAINST (:query IN BOOLEAN MODE)';
				break;
			
			case static::TYPE_WITH_QUERY_EXPANSION:
				$query .= 'AGAINST (:query WITH QUERY EXPANSION)';
				break;
			
			case static::TYPE_IN_NATURAL_LANGUAGE_WITH_QUERY_EXPANSION:
				$query .= 'AGAINST (:query IN NATURAL LANGUAGE MODE WITH QUERY EXPANSION)';
		}
		
		return $query;
	}
	
	/**
	 * @param \PDOStatement $stmt
	 * @param \Supra\Search\Request\SearchRequestInterface $request
	 */
	protected function bindSearchValues(\Doctrine\DBAL\Statement $stmt, SearchRequestInterface $request)
	{
		$words = explode(' ', $request->getText());
		$propperWords = array();
		
		foreach ($words as $word) {
			if (mb_strlen($word) >= self::MIN_WORD_LENGTH) {
				$propperWords[] = $word;
			}
		}
		
		$text = implode(' ', $propperWords);
		
		// Add asterisk in case of BOOLEAN MODE
		if ($this->type == static::TYPE_BOOLEAN 
				&& ! empty($text)) {
			
			$text .= '*';
		}
		
		$stmt->bindValue(':locale', $request->getLocale()->getId(), \PDO::PARAM_STR);
		$stmt->bindValue(':query', $text, \PDO::PARAM_STR);
	}
}