<?php

namespace Supra\Search\Result\Abstraction;

use Supra\Search\Result\SearchResultSetInterface;
use Supra\Search\Result\SearchResultItemInterface;
use Supra\Search\Result\Exception;

abstract class SearchResultSetAbstraction implements SearchResultSetInterface
{

	/**
	 * @var array 
	 */
	protected $items;

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

	public function getTotalResultCount()
	{
		return $this->totalResultCount;
	}
	
	public function setTotalResultCount($totalResultCount)
	{
		$this->totalResultCount = $totalResultCount;
	}

}
