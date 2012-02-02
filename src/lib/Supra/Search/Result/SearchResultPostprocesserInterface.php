<?php

namespace Supra\Search\Result;

use Supra\Search\Result\SearchResultSetInterface;

interface SearchResultPostprocesserInterface
{
	public function getClasses();
	
	public function postprocessResultSet(SearchResultSetInterface $resultSet);
}
