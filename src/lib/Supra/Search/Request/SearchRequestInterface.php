<?php

namespace Supra\Search\Request;

use Solarium_Query_Select;
use Solarium_Result_Select;

interface SearchRequestInterface
{

	public function applyParametersToSelectQuery(Solarium_Query_Select $selectQuery);

	public function addSimpleFilter($name, $value);
	
	public function processResults(Solarium_Result_Select $selectResults);
}
