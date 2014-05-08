<?php

namespace Supra\Search;

class SearchService
{
	/**
	 * @var AbstractSearcher
	 */
	protected $searcher;
	
	/**
	 * @param AbstractSearcher $searcher
	 */
	public function __construct(AbstractSearcher $searcher = null)
	{
		if ($searcher !== null) {
			$this->searcher = $searcher;
		}
	}
	
	/**
	 * @param AbstractSearcher $searcher
	 */
	public function setSearcher(AbstractSearcher $searcher)
	{
		$this->searcher = $searcher;
	}
	
	/**
	 * @return AbstractSearcher
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
