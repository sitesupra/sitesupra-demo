<?php

namespace Supra\Search\Result\Abstraction;

use Supra\Search\Result\SearchResultSetInterface;
use Supra\Search\Result\SearchResultItemInterface;
use Supra\Search\Result\Exception;
use Supra\Search\Result\SearchResultPostprocesserInterface;
use Supra\Search\Result\DefaultSearchResultSet;

abstract class SearchResultSetAbstraction implements SearchResultSetInterface
{

	/**
	 * @var array 
	 */
	protected $items;

	/**
	 *
	 * @var array
	 */
	protected $postprocessers;

	/**
	 * @var boolean
	 */
	protected $postprocessersRun;

	/**
	 * @var integer
	 */
	protected $totalResultCount;

	function __construct()
	{
		$this->items = array();
		$this->totalResultCount = 0;
	}

	/**
	 * @param SearchResultItemInterface $item 
	 */
	public function add(SearchResultItemInterface $item)
	{
		$itemId = $item->getUniqueId();

		if (empty($itemId)) {
			throw new Exception\RuntimeException('Search result item has no id.');
		}

		if (empty($this->items[$itemId])) {
			$this->items[$itemId] = $item;
		}
	}

	/**
	 * @return array
	 */
	public function getItems()
	{
		return $this->items;
	}

	/**
	 * @return integer
	 */
	public function getItemCount()
	{
		return count($this->items);
	}
	
	/**
	 * Returns total count of matched (but not returned!) items in resultset.
	 * @return integer
	 */
	public function getTotalResultCount()
	{
		return $this->totalResultCount;
	}
	
	/**
	 * Set total count of matched (but not returned!) items in resultset.
	 * @param integer $totalResultCount 
	 */
	public function setTotalResultCount($totalResultCount)
	{
		$this->totalResultCount = $totalResultCount;
	}

	/**
	 * @param SearchResultPostprocesserInterface $postprocesser 
	 */
	public function addPostprocesser(SearchResultPostprocesserInterface $postprocesser)
	{
		$this->postprocessers[] = $postprocesser;
	}

	public function runPostprocessers()
	{
		if ( ! $this->postprocessersRun) {

			foreach ($this->postprocessers as $postprocesser) {
				/* @var $postprocesser SearchResultPostprocesserInterface */
				$items = $this->getItems();

				$postprocesserClasses = $postprocesser->getClasses();

				$relevantItemSet = $items;
				
				if ( ! empty($postprocesserClasses)) {

					$relevantItemSet = new DefaultSearchResultSet();

					foreach ($items as $item) {
						/* @var $item SearchResultItemInterface */
						
						if (in_array($item->getClass(), $postprocesserClasses)) {
							$relevantItemSet->add($item);
						}
					}
				}
				
				$postprocesser->postprocessResultSet($relevantItemSet);
				
				$this->postprocessersRun;
			}
		}
	}

}
