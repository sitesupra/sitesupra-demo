<?php

namespace Supra\Search\Solarium;

use Supra\ObjectRepository\ObjectRepository;
use Supra\Search\SearchServiceAdapter;
use Supra\Search\SearchService;
use Supra\Controller\Pages\Search\PageLocalizationSearchRequest;
use Supra\Controller\Pages\PageController;
use Supra\Controller\Pages\Search\PageLocalizationSearchResultPostProcesser;
use Supra\Search\Result\DefaultSearchResultSet;
use Solarium_Client;
use Solarium_Exception;
use Solarium_Document_ReadWrite;
use Supra\Search\Entity\Abstraction\IndexerQueueItem;
use Request\SearchRequestAbstraction;
use Supra\Log\Writer\WriterAbstraction;
use \Solarium_Result_Select;

class Adapter extends SearchServiceAdapter {

	const FAILED_TO_GET_CLIENT_MESSAGE = 'Solr search engine is not configured.';

	public $defaultAdapterClass = '\Solarium_Client_Adapter_Http';

	public function configure() {
		static $isConfigured = FALSE;

		if ($isConfigured) {
			return TRUE;
		}

		$ini = ObjectRepository::getIniConfigurationLoader('');

		if (!$ini->hasSection('solarium')) {
			\Log::debug(self::FAILED_TO_GET_CLIENT_MESSAGE);
			return;
		}

		$searchParams = $ini->getSection('solarium');

		$adapterClass = $this->defaultAdapterClass;

		if (!empty($searchParams['adapter'])) {
			$adapterClass = $searchParams['adapter'];
		}

		$options = array(
			'adapter' => $adapterClass,
			'adapteroptions' => $searchParams
		);

		$solariumClient = new Solarium_Client($options);

		ObjectRepository::setDefaultSolariumClient($solariumClient);
		$isConfigured = TRUE;
	}

	/**
	 * @param Request\SearchRequestInterface $request
	 * @return Result\SearchResultSetInterface
	 */
	public function processRequest(\Supra\Search\Request\SearchRequestInterface $request) {
		if (!ObjectRepository::isSolariumConfigured($this)) {
			\Log::debug(Configuration::FAILED_TO_GET_CLIENT_MESSAGE);
			return new DefaultSearchResultSet();
		}

		$solariumClient = ObjectRepository::getSolariumClient($this);
		$selectQuery = $solariumClient->createSelect();

		$request->addSimpleFilter('systemId', $this->getSystemId());

		$request->applyParametersToSelectQuery($selectQuery);

		$this->log->debug('SOLARIUM QUERY: ', $selectQuery->getQuery());

		$filters = array();
		foreach ($selectQuery->getFilterQueries() as $filterQuery) {
			$filters[] = $filterQuery->getQuery();
		}
		$this->log->debug('SOLARIUM QUERY FILTER: ', join(' AND ', $filters));

		$selectResults = $solariumClient->select($selectQuery);

		$requestResults = $request->processResults($selectResults);

		return $requestResults;
	}

	/**
	 * @param string $text
	 * @return Result\DefaultSearchResultSet
	 */
	public function doSearch($text, $maxRows, $startRow) {
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