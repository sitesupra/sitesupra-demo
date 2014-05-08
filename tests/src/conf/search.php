<?php

use Supra\ObjectRepository\ObjectRepository;
use Supra\Search\IndexerService;
use Supra\Search\SearchService;
use Supra\Search\Mysql;
use Supra\Search\Solarium;

$mysqlIndexerService = new IndexerService(new Mysql\MysqlIndexer);
$mysqlSearchService = new SearchService(new Mysql\MysqlSearcher);

ObjectRepository::setSearchService('Supra\Tests\Search\Mysql', $mysqlSearchService);
ObjectRepository::setSearchService('Supra\Search\Mysql', $mysqlSearchService);
ObjectRepository::setIndexerService('Supra\Tests\Search\Mysql', $mysqlIndexerService);
ObjectRepository::setIndexerService('Supra\Search\Mysql', $mysqlIndexerService);

$solrClient = new \Solarium_Client;

$solrIndexerService = new IndexerService(new Solarium\SolariumIndexer($solrClient));
$solrSearchService = new SearchService(new Solarium\SolariumSearcher($solrClient));

ObjectRepository::setSearchService('Supra\Tests\Search\Solarium', $solrSearchService);
ObjectRepository::setSearchService('Supra\Search\Solarium', $solrSearchService);
ObjectRepository::setIndexerService('Supra\Tests\Search\Solarium', $solrIndexerService);
ObjectRepository::setIndexerService('Supra\Search\Solarium', $solrIndexerService);