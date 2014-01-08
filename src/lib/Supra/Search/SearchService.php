<?php

namespace Supra\Search;

use Supra\ObjectRepository\ObjectRepository;
use Request\SearchRequestAbstraction;

class SearchService
{
	/**
	 * @var Singelton
	 */
	protected static $adapter = array();
	
	/**
	 * @return \Supra\Search\{Adapter}\Adapter
	 */
	public static function getAdapter( $adapter = NULL )
	{
		$adapter = ( $adapter == NULL ) ? SEARCH_SERVICE_ADAPTER : $adapter;
		$adapterClass = '\\Supra\\Search\\' . $adapter . '\\Adapter';
		
		if ( !isset( SearchService::$adapter[$adapter] ) )
		{
			SearchService::$adapter[$adapter] = new $adapterClass();
			//SearchService::$adapter[$adapter]->configure();
		}
		
		return SearchService::$adapter[$adapter];
	}
	
	/**
	 * @param Request\SearchRequestInterface $request
	 * @return Supra\Search\Adapter\AdapterResult\SearchResultSetInterface
	 */
	public function processRequest(Request\SearchRequestInterface $request)
	{
		return SearchService::getAdapter()->processRequest($request);
	}
}
