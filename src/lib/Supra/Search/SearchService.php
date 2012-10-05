<?php

namespace Supra\Search;

use Solarium_Client;
use Solarium_Exception;
use Solarium_Document_ReadWrite;
use Supra\Search\Entity\Abstraction\IndexerQueueItem;
use Supra\ObjectRepository\ObjectRepository;
use Request\SearchRequestAbstraction;
use Supra\Log\Writer\WriterAbstraction;
use \Solarium_Result_Select;
use Supra\Search\Solarium\Configuration;

class SearchService
{
	/**
	 * @var WriterAbstraction
	 */
	private $log;
	
	/**
	 * System ID to be used for this project.
	 * @var string
	 */
	private $systemId;

	function __construct()
	{
		$this->log = ObjectRepository::getLogger($this);
	}
	
	/**
	 * @return string
	 */
	public function getSystemId()
	{
		if (is_null($this->systemId)) {
			$info = ObjectRepository::getSystemInfo($this);
			$this->systemId = $info->name;
		}
		
		return $this->systemId;
	}

	/**
	 * @param Request\SearchRequestInterface $request
	 * @return Result\SearchResultSetInterface
	 */
	public function processRequest(Request\SearchRequestInterface $request)
	{
		if ( ! ObjectRepository::isSolariumConfigured($this)) {
			\Log::debug(Configuration::FAILED_TO_GET_CLIENT_MESSAGE);
			return new Result\DefaultSearchResultSet();
		}

		$solariumClient = ObjectRepository::getSolariumClient($this);
		$selectQuery = $solariumClient->createSelect();

		$request->addSimpleFilter('systemId', $this->getSystemId());

		$request->applyParametersToSelectQuery($selectQuery);

		$this->log->debug('SOLARIUM QUERY: ', $selectQuery->getQuery());
		
		$filters = array();
		foreach($selectQuery->getFilterQueries() as $filterQuery) {
			$filters[] = $filterQuery->getQuery();
		}
		$this->log->debug('SOLARIUM QUERY FILTER: ', join(' AND ', $filters));

		$selectResults = $solariumClient->select($selectQuery);

		$requestResults = $request->processResults($selectResults);
		
		return $requestResults;
	}

}
