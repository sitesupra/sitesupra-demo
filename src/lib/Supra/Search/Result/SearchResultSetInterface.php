<?php

namespace Supra\Search\Result;

interface SearchResultSetInterface
{

	public function add(SearchResultItemInterface $item);

	public function getItems();

	public function getItemCount();

	public function getTotalResultCount();

	public function setTotalResultCount($totalResultCount);
}
