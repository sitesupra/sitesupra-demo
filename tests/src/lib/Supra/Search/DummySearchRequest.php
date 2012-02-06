<?php

namespace Supra\Tests\Search;

use Supra\Search\Request\Abstraction\EntitySearchRequestAbstraction;
use \Solarium_Query_Select;
use \Solarium_Result_Select;
use Supra\Tests\Search\DummyItem;

class DummySearchRequest extends EntitySearchRequestAbstraction
{

	/**
	 * @var string
	 */
	protected $text;

	function __construct()
	{
		parent::__construct(DummyItem::CN());
	}

	/**
	 * @param string $text 
	 */
	public function setText($text)
	{
		$this->text = $text;
	}

	/**
	 * @param Solarium_Query_Select $selectQuery 
	 */
	public function applyParametersToSelectQuery(Solarium_Query_Select $selectQuery)
	{
		parent::applyParametersToSelectQuery($selectQuery);

		$selectQuery->setQuery($this->text);
	}
	
	public function processResults(Solarium_Result_Select $selectResults)
	{
		return array();
	}
}

