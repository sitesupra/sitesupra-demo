<?php

namespace Supra\Search;

use Solarium_Client;
use Solarium_Exception;
use Solarium_Document_ReadWrite;
use Supra\Search\Entity\Abstraction\IndexerQueueItem;
use Supra\ObjectRepository\ObjectRepository;
use Request\SearchRequestAbstraction;
use Supra\Log\Writer\WriterAbstraction;
use Solarium_Result_Select;

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
	 * @return Solarium_Result_Select
	 */
	public function processRequest(Request\SearchRequestInterface $request)
	{
		$solariumClient = ObjectRepository::getSolariumClient($this);
		$selectQuery = $solariumClient->createSelect();

		$request->addSimpleFilter('systemId', $this->getSystemId());

		$request->applyParametersToSelectQuery($selectQuery);

		$this->log->debug('SOLARIUM QUERY: ', $selectQuery->getQuery());

		$selectResults = $solariumClient->select($selectQuery);

		$requestResults = $request->processResults($selectResults);

		return $requestResults;
	}

}
