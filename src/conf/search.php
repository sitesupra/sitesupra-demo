<?php

use Supra\ObjectRepository\ObjectRepository;
use Supra\Search\Mysql\MysqlIndexer;
use Supra\Search\Mysql\MysqlSearcher;

ObjectRepository::setDefaultSearchService(
		new Search\SearchService(new MysqlSearcher));

ObjectRepository::setDefaultIndexerService(
		new Search\IndexerService(new MysqlIndexer));