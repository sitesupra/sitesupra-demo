<?php

namespace Supra\Controller\Pages\Search;

use \Solarium_Query_Select;
use \Solarium_Result_Select;
use Supra\Controller\Pages\Entity\PageLocalization;
use Supra\Search\Request\Abstraction\SearchRequestAbstraction;
use Supra\Locale\Locale;
use Supra\ObjectRepository\ObjectRepository;

class PageLocalizationFindRequest extends SearchRequestAbstraction
{

	protected $schemaName = null;
	protected $pageLocalizationId = null;
	protected $revisionId = null;

	public function setSchemaName($schemaName)
	{
		$this->schemaName = $schemaName;
	}

	public function setPageLocalizationId($pageLocalizationId)
	{
		$this->pageLocalizationId = $pageLocalizationId;
	}

	public function setRevisionId($revisionId)
	{
		$this->revisionId = $revisionId;
	}

	public function applyParametersToSelectQuery(Solarium_Query_Select $selectQuery)
	{

		$query = array();

		if ( ! empty($this->schemaName)) {
			$query[] = 'schemaName:"' . $this->schemaName . '"';
		}

		if ( ! empty($this->pageLocalizationId)) {
			$query[] = 'pageLocalizationId:' . $this->pageLocalizationId;
		}

		if ( ! empty($this->revisionId)) {
			$query[] = 'revisionId:' . $this->revisionId;
		}

		$selectQuery->setQuery(join(' AND ', $query));
	}

	public function processResults(Solarium_Result_Select $selectResults)
	{
		return $selectResults;
	}

}
