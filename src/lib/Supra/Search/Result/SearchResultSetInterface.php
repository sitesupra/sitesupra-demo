<?php

namespace Supra\Search\Result;

use Supra\Search\Result\SearchResultPostprocesserInterface;

interface SearchResultSetInterface
{

	public function add(SearchResultItemInterface $item);

	public function getItems();

	public function getItemCount();

	public function getTotalResultCount();

	public function setTotalResultCount($totalResultCount);
	
	public function addPostprocesser(SearchResultPostprocesserInterface $postprocesser);
	
	public function runPostprocessers();
}
