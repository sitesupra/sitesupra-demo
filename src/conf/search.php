<?php

use Supra\ObjectRepository\ObjectRepository;
use Supra\Search;
use Supra\Search\Mysql;

ObjectRepository::setDefaultSearchService(
		new Search\SearchService(new Mysql\MysqlSearcher));

ObjectRepository::setDefaultIndexerService(
		new Search\IndexerService(new Mysql\MysqlIndexer));