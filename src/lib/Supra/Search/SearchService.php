<?php

namespace Supra\Search;

use \Solarium_Client;
use \Solarium_Exception;
use \Solarium_Document_ReadWrite;
use Supra\Search\Entity\Abstraction\IndexerQueueItem;

class SearchService
{
	/**
	 * @var \Solarium_Client;
	 */
	private $solariumClient;

	/**
	 * System ID to be used for this project.
	 * @var string
	 */
	private $systemId;

	function __construct()
	{
		$this->systemId = 'someSystemId';

		$config = array(
				'adapteroptions' => array(
						'host' => '127.0.0.1',
						'port' => 8080,
						'path' => '/solrdev',
				)
		);

		$this->solariumClient = new Solarium_Client($config);

		//$pingQuery = $this->solariumClient->createPing();
		//$this->solariumClient->ping($pingQuery);
	}

	public function search($criteria) 
	{
		// get a select query instance
		$query = $this->solariumClient->createSelect();

		// set a query (all prices starting from 12)
		$query->setQuery('price:[12 TO *]');

		// set start and rows param (comparable to SQL limit) using fluent interface
		$query->setStart(2)->setRows(20);

		// set fields to fetch (this overrides the default setting 'all fields')
		$query->setFields(array('id','name','price'));

		// sort the results by price ascending
		$query->addSort('price', Solarium_Query_Select::SORT_ASC);

		// this executes the query and returns the result
		$resultset = $this->solariumClient->select($query);

		// display the total number of documents found by solr
		echo 'NumFound: '.$resultset->getNumFound();

		// show documents using the resultset iterator
		foreach ($resultset as $document) {		
			\Log::debug('DDD: ', $document);
		}
	}
}
