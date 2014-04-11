<?php

namespace Supra\Search;

use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Search\PageLocalizationSearchResultPostProcesser;
use Supra\Controller\Pages\Search\PageLocalizationSearchRequest;
use Supra\Controller\Pages\PageController;

abstract class SearcherAbstract 
{
	/**
	 * @var \Supra\Log\Writer\WriterAbstraction
	 */
	protected $log;
	
	/**
	 * System ID to be used for this project.
	 * @var string
	 */
	protected $systemId;
	
	/**
	 */
	public function __construct()
	{
		$this->log = ObjectRepository::getLogger($this);
	}
	
	/**
	 * @return string
	 */
	public function getSystemId()
	{
		if ($this->systemId === null) {
			$this->systemId = ObjectRepository::getSystemInfo($this)->name;
		}
		
		return $this->systemId;
	}
	
	abstract public function processRequest(\Supra\Search\Request\SearchRequestInterface $request);
		
	/**
	 * @param string $text
	 * @param int $maxRows
	 * @param int $startRow
	 * @return Result\DefaultSearchResultSet
	 */
	public function doSearch($text, $maxRows, $startRow)
	{
		$lm = ObjectRepository::getLocaleManager($this);
		$locale = $lm->getCurrent();

		$searchRequest = new PageLocalizationSearchRequest();

		$searchRequest->setResultMaxRows($maxRows);
		$searchRequest->setResultStartRow($startRow);
		$searchRequest->setText($text);
		$searchRequest->setLocale($locale);
		$searchRequest->setSchemaName(PageController::SCHEMA_PUBLIC);

		$results = $this->processRequest($searchRequest);

		$pageLocalizationPostProcesser = new PageLocalizationSearchResultPostProcesser();
		$results->addPostprocesser($pageLocalizationPostProcesser);

		$results->runPostprocessers();

		return $results;
	}
}