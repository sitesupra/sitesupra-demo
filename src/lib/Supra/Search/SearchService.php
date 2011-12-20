<?php

namespace Supra\Search;

use Solarium_Client;
use Solarium_Exception;
use Solarium_Document_ReadWrite;
use Supra\Search\Entity\Abstraction\IndexerQueueItem;
use Supra\ObjectRepository\ObjectRepository;
use Request\SearchRequestAbstraction;
use Supra\Log\Writer\WriterAbstraction;

class SearchService
{
	/**
	 * @var WriterAbstraction
	 */
	private $log;
	
	/**
	 * @var Solarium_Client;
	 */
	protected $solariumClient;

	function __construct()
	{
		$this->solariumClient = ObjectRepository::getSolariumClient($this);
		$this->log = ObjectRepository::getLogger($this);
		
		$configurationLoader = ObjectRepository::getIniConfigurationLoader($this);
		$this->systemId = $configurationLoader->getValue('solarium', 'systemId');
	}

	/**
	 * @param Request\SearchRequestInterface $request
	 * @return Solarium_Result_Select
	 */
	public function processRequest($request)
	{
		$selectQuery = $this->solariumClient->createSelect();

		$request->addSimpleFilter('systemId', $this->systemId);

		$request->applyParametersToSelectQuery($selectQuery);

		$this->log->debug('SOLARIUM QUERY: ', $selectQuery->getQuery());

		$selectResults = $this->solariumClient->select($selectQuery);

		$requestResults = $request->processResults($selectResults);

		return $requestResults;
	}

}