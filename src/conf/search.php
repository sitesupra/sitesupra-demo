<?php

$searchService = \Supra\Search\SearchService::getInstance();
$searchService->setSearcher(new \Supra\Search\Mysql\MysqlSearcher);

$indexerService = Supra\Search\IndexerService::getInstance();
$indexerService->setIndexer(new Supra\Search\Mysql\MysqlIndexer);