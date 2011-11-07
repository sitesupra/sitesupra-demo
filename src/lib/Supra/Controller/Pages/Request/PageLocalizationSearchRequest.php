<?php

namespace Supra\Controller\Pages\Request;

use \Solarium_Query_Select;
use Supra\Controller\Pages\Entity\PageLocalization;
use Supra\Search\Request\Abstraction\SearchRequestAbstraction;

class PageLocalizationSearchRequest extends SearchRequestAbstraction
{

	/**
	 * @var string
	 */
	protected $locale;

	/**
	 * @var string
	 */
	protected $schemaName;

	/**

	 * @var string
	 */
	protected $text;

	function __construct()
	{
		$this->addFilterQuery('class', PageLocalization::CN());
	}

	/**
	 * @param string $locale 
	 */
	public function setLocale($locale)
	{
		$this->locale = $locale;
	}

	/**
	 * @param string $schemaName 
	 */
	public function setSchemaName($schemaName)
	{
		$this->schemaName = $schemaName;
	}

	/**
	 * @param string $text 
	 */
	public function setText($text)
	{
		$this->text = $text;
	}
	
	/**
	 * @param Solarium_Query_Select $selectQuery 
	 */
	public function applyQueryParameters(Solarium_Query_Select $selectQuery)
	{
		$this->addSimpleFilter('schemaName', $this->schemaName);

		if ( ! empty($this->locale)) {
			$this->addSimpleFilter('locale', $this->locale);
		}

		$selectQuery->setQuery($this->text);
	}
	
	/**
	 * @param string $order 
	 */
	public function setOrderByCreateTime($order = SearchRequestAbstraction::ORDER_ASC)
	{
		$this->setSortFieldAndOrder('createTime', $order);
	}
	
	/**
	 * @param string $order 
	 */
	public function setOrderByModifyTime($order = SearchRequestAbstraction::ORDER_ASC)
	{
		$this->setSortFieldAndOrder('modifyTime', $order);
	}

}
