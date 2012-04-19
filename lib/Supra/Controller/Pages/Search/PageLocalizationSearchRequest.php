<?php

namespace Supra\Controller\Pages\Search;

use \Solarium_Query_Select;
use \Solarium_Result_Select;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Locale\Locale;
use Supra\Controller\Pages\Entity\PageLocalization;
use Supra\Search\Request\Abstraction\SearchRequestAbstraction;
use Supra\Search\Result\DefaultSearchResultSet;

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
		$this->addSimpleFilter('class', PageLocalization::CN());
	}

	/**
	 * @param Locale $locale 
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
	public function applyParametersToSelectQuery(Solarium_Query_Select $selectQuery)
	{
		$this->addSimpleFilter('schemaName', $this->schemaName);

		$this->addSimpleFilter('isActive', 'true');
		$this->addSimpleFilter('includeInSearch', 'true');
		$this->addSimpleFilter('isRedirected', 'false');
		
		$isAuthorized = false;
		$userProvider = ObjectRepository::getUserProvider($this, false);
		
		if ($userProvider instanceof \Supra\User\UserProviderAbstract) {
			$user = $userProvider->getSignedInUser(false);
			
			if ($user instanceof \Supra\User\Entity\User) {
				$isAuthorized = true;
			}
		}
	
		if ( ! $isAuthorized) {
			$this->addSimpleFilter('isLimited', 'false');
		}

		// This is default for case when locale is not set for this request.
		$languageCode = 'general';

		if ( ! empty($this->locale)) {

			$this->addSimpleFilter('localeId', $this->locale->getId());

			$languageCode = $this->locale->getProperty('language');
		}

		$textFieldName = 'text_' . $languageCode;

		$this->highlightPrefix = '<b>';
		$this->highlightPostfix = '</b>';
		$this->highlightedFields = array($textFieldName);

		$helper = $selectQuery->getHelper();

		$solrQuery = $textFieldName . ':' . $helper->escapePhrase($this->text);

		$selectQuery->setQuery($solrQuery);

		parent::applyParametersToSelectQuery($selectQuery);
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

	/**
	 * @param Solarium_Result_Select $selectResults 
	 * @return DefaultSearchResultSet
	 */
	public function processResults(Solarium_Result_Select $selectResults)
	{
		$resultSet = new DefaultSearchResultSet();
		
		$highlighting = $selectResults->getHighlighting();

		foreach ($selectResults as $selectResultItem) {

			if ($selectResultItem->class == PageLocalization::CN()) {

				$item = new PageLocalizationSearchResultItem($selectResultItem, $highlighting);

				$resultSet->add($item);
			}
		}

		$resultSet->setTotalResultCount($selectResults->getNumFound());

		return $resultSet;
	}

}
