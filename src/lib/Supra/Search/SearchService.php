<?php

namespace Supra\Search;

class SearchService
{
	/**
	 * @var self
	 */
	private static $instance;
	
	/**
	 * @var SearcherAbstract
	 */
	protected $searcher;
	
	/**
	 * @TODO: move to object repo
	 * @return \Supra\Search\SearchService
	 */
	public static function getInstance()
	{
		if (self::$instance === null) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}
	
	/**
	 * @param \Supra\Search\SearcherAbstract $searcher
	 */
	public function setSearcher(SearcherAbstract $searcher)
	{
		$this->searcher = $searcher;
	}
	
	/**
	 * @return \Supra\Search\SearcherAbstract
	 */
	public function getSearcher()
	{
		return $this->searcher;
	}
	
	/**
	 * @param Request\SearchRequestInterface $request
	 * @return Result\SearchResultSetInterface
	 */
	public function processRequest(Request\SearchRequestInterface $request)
	{
		return $this->searcher->processRequest($request);
	}
	
	/**
	 * @param string $text
	 * @param int $maxRows
	 * @param int $startRow
	 * @return Result\DefaultSearchResultSet
	 */
	public function doSearch($text, $maxRows, $startRow)
	{
		return $this->searcher->doSearch($text, $maxRows, $startRow);
	}
}
