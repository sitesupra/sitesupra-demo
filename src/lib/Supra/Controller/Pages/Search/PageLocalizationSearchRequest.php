<?php

namespace Supra\Controller\Pages\Search;

use \Solarium_Query_Select;
use Supra\Controller\Pages\Entity\PageLocalization;
use Supra\Search\Request\Abstraction\SearchRequestAbstraction;
use Supra\Locale\Locale;
use \Solarium_Result_Select;
use Supra\ObjectRepository\ObjectRepository;

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

		$textFieldName = null;
		$titleFieldName = null;

		// This is default for case when locale is not set for this request.
		$languageCode = 'general';

		if ( ! empty($this->locale)) {

			$this->addSimpleFilter('localeId', $this->locale->getId());

			$languageCode = $this->locale->getProperty("language");
		}

		$textFieldName = 'text_' . $languageCode;
		$titleFieldName = 'title_' . $languageCode;

		$this->hilightPrefix = '<b>';
		$this->hilightPostfix = '</b>';
		$this->hilightedFields = array($textFieldName, $titleFieldName);

		$helper = $selectQuery->getHelper();

		$solrQuery = array(
				$textFieldName . ':' . $helper->escapePhrase($this->text),
				$titleFieldName . ':' . $helper->escapePhrase($this->text)
		);

		$solrQuery = join(' OR ', $solrQuery);

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
	 */
	public function processResults(Solarium_Result_Select $selectResults)
	{
		$results = array();

		$highlighting = $selectResults->getHighlighting();

		foreach ($selectResults as $selectResultItem) {

			$result = array(
					'localeId' => $selectResultItem->localeId,
					'pageWebPath' => $selectResultItem->pageWebPath,
					'title' => $selectResultItem->title_general,
					'text' => $selectResultItem->text_general
			);

			if ( ! empty($highlighting)) {

				$highlightedResultItem = $highlighting->getResult($selectResultItem->uniqueId);

				if ($highlightedResultItem) {

					foreach ($highlightedResultItem as $highlight) {
						$result['hilight'] = implode(' (...) ', $highlight);
					}
				}
			}

			$results[] = $result;
		}

		return $results;
	}

}
