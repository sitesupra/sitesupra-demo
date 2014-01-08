<?php

define( 'SEARCH_SERVICE_ADAPTER_SOLARIUM', 'Solarium' );
define( 'SEARCH_SERVICE_ADAPTER_MYSQL', 'Mysql' );

// Adapter by Default
define( 'SEARCH_SERVICE_ADAPTER', SEARCH_SERVICE_ADAPTER_MYSQL );

/**
 * MySQL FULLTEXT MODE LIST:
 * 
 * MATCH-AGAINST IN NATURAL LANGUAGE MODE
 * - TYPE_DEFAULT
 * 
 * MATCH-AGAINST IN BOOLEAN MODE
 * - TYPE_BOOLEAN
 * 
 * MATCH-AGAINST IN NATURAL LANGUAGE MODE WITH QUERY EXPANSION
 * - TYPE_QUERY_EXPANSION
 */
// Mysql Fulltext default mode
define( 'SEARCH_SERVICE_FULLTEXT_DEFAULT_MODE', Supra\Search\Mysql\Adapter::TYPE_BOOLEAN );

// Mysql MyISAM Table
define( 'SEARCH_SERVICE_FULLTEXT_TABLE', 'search_content' );

// How much letters cut before and after search query
define( 'SEARCH_SERVICE_FUULTEXT_HIGHLIGHT_LENGTH', 400 );

/**
 * Configuration solution example:
 * 
 * $searchServiceAdapter = new Supra\Search\Mysql\MysqlAdapter;
 * SearchService::setAdapter($searchServiceAdapter);
 * 
 * $indexerServiceAdapter = new Supra\Search\Mysql\MysqlIndexerServiceAdapter;
 * IndexerService::setAdapter($indexerServiceAdapter);
 * 
 * // While getAdapter method would consist only from, something like this:
 * public static function getAdapter()
 * {
 *		if (self::$serviceAdapter === null) {
 *			throw new \RuntimeException('Search service is not configured properly, check the search.php');
 *		}
 * 
 *		return self::$serviceAdapter;
 * }
 * 
 * 
 */

\Supra\Search\SearchService::getAdapter()->configure();
