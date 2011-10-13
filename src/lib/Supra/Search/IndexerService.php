<?php

namespace Supra\Search;

use \Solarium_Client;
use \Solarium_Exception;

class IndexerService
{

	/**
	 * @var \Solarium_Client;
	 */
	private $solariumClient;

	function __construct()
	{
		$this->solariumClient = new \Solarium_Client();

		$pingQuery = $this->solariumClient->createPing();

		try {
			$this->solariumClient->ping($pingQuery);
			\Log::debug('Ping query succesful');
		}
		catch (Solarium_Exception $e) {
			\Log::debug('Ping query failed ', $e);
		}
	}

	public function addToIndex($data)
	{
		
	}

}
