<?php

namespace Supra\Search;

use Supra\Controller\Pages\BlockController;
use Supra\Request;
use Supra\Controller\Pages\Request\PageRequestView;
use Supra\Response;
use Supra\Search\SearchService;
use Supra\Controller\Pages\Search\PageLocalizationSearchRequest;
use Supra\Controller\Pages\PageController;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Uri\Path;
use Supra\Controller\Pages\BlockControllerCollection;
use Supra\Controller\Pages\Entity\Abstraction\Block as PageBlockAbstraction;
use Supra\Controller\Pages\Set\BlockPropertySet as PageBlockPropertySet;
use Supra\Controller\Pages\Entity\BlockProperty;
use Supra\Controller\Pages\Entity\Page;
use Supra\Controller\Pages\Entity\PageLocalization;
use Supra\Controller\Pages\Configuration\BlockControllerConfiguration;

/**
 * Simple text block
 */
class SearchController extends BlockController
{
	const PROPERTY_NAME_BLOCK_TYPE = 'blockType';
	const BLOCK_TYPE_FORM = 'form';
	const BLOCK_TYPE_RESULTS = 'results';

	const ADDITIONAL_RESPONSE_DATA_KEY_RESULTS = 'search-results';

	//static $results = null;

	public function createResponse(Request\RequestInterface $request)
	{
		$response = new Response\TwigResponse();

		return $response;
	}
	
	/**
	 * Accepts only SearchControllerConfiguration instances
	 * @param BlockControllerConfiguration $configuration 
	 */
	public function setConfiguration(BlockControllerConfiguration $configuration)
	{
		if ( ! $configuration instanceof SearchControllerConfiguration) {
			throw new Exception\RuntimeException("Search controller accepts only SearchControllerConfiguration as configuration");
		}
		
		$this->configuration = $configuration;
	}

	/**
	 * @return SearchControllerConfiguration
	 */
	public function getConfiguration()
	{
		return $this->configuration;
	}

	public function execute()
	{
		$blockType = $this->getPropertyValue(self::PROPERTY_NAME_BLOCK_TYPE);

		if ($blockType == self::BLOCK_TYPE_FORM) {

			//$this->response = $this->formResponse;
			//$this->getResults();
			$this->executeForm();
		} else if ($blockType == self::BLOCK_TYPE_RESULTS) {

			//$this->response = $this->resultsResponse;
			//$this->getResults();
			$this->executeResults();
		}
	}

	public function executeForm()
	{
		$response = $this->getResponse();
		$configuration = $this->getConfiguration();
		$results = $this->getResults();

		if ($results instanceof \Supra\Search\Exception\RuntimeException) {

			$response->assign('error', true);
		} else if ( ! empty($results->processedResults)) {

			/* @var $results \Solarium_Result_Select */

			$response->assign('haveResults', true);
			$response->assign('resultCount', $results->getNumFound());
		}

		$response->outputTemplate('template/' . $configuration->formTemplateFilename);
	}

	protected function executeResults()
	{
		$response = $this->getResponse();
		$configuration = $this->getConfiguration();
		$results = $this->getResults();

		if (empty($results->processedResults)) {
			$response->outputTemplate('template/' . $configuration->noResultsTemplateFilename);
		} else if ($results instanceof \Supra\Search\Exception\RuntimeException) {

			$response->assign('error', true);
			$response->outputTemplate('template/' . $configuration->resultsTemplateFilename);
		} else {
			/* @var $results \Solarium_Result_Select */

			$em = ObjectRepository::getEntityManager($this);

			$pr = $em->getRepository(PageLocalization::CN());

			foreach ($results->processedResults as &$result) {

				$result['breadcrumbs'] = array();

				$ancestorIds = array_reverse($result['ancestorIds']);

				foreach ($ancestorIds as $ancestorId) {

					$p = $pr->find($ancestorId);

					if ($p instanceof Page) {

						$pl = $p->getLocalization($result['localeId']);
						$result['breadcrumbs'][] = $pl->getTitle();
					} else if ($p instanceof PageLocalization) {
						$result['breadcrumbs'][] = $p->getTitle();
					} elseif ($p instanceof \Supra\Controller\Pages\Entity\GroupPage) {
						$result['breadcrumbs'][] = $p->getTitle();
					}
				}
			}

			$totalPages = ceil($results->getNumFound() / $configuration->resultsPerPage);
			$response->assign('resultsPerPage', $configuration->resultsPerPage);
			$response->assign('searchResults', $results->processedResults);
			$response->assign('pages', range(1, $totalPages));
			$response->assign('pageCount', $totalPages);

			//$response->getContext()
			//		->addJsToLayoutSnippet('js', 'alert(123);');

			$response->outputTemplate('template/' . $configuration->resultsTemplateFilename);
		}
	}

	protected function getResults()
	{
		$request = $this->getRequest();
		$configuration = $this->getConfiguration();
		$response = $this->getResponse();
		/* @var $response Response\TwigResponse */

		$q = $request->getQueryValue('q');
		$response->assign('q', $q);

		$currentPageNumber = $request->getQueryValue('p', 0);
		$response->assign('currentPageNumber', $currentPageNumber);

		$results = $response->getContext()
				->getValue(self::ADDITIONAL_RESPONSE_DATA_KEY_RESULTS);

		if ( ! is_null($results)) {
			return $results;
		}

		if ($request instanceof PageRequestView) {

			if ( ! is_null($q)) {

				$path = $request->getPath();

				if ( ! empty($path)) {
					$resultUrl = $path->getFullPath(Path::FORMAT_BOTH_DELIMITERS);
					$response->assign('resultUrl', $resultUrl);
				}

				try {
					$results = $this->doSearch($q, $configuration->resultsPerPage, abs(intval($currentPageNumber) * intval($configuration->resultsPerPage)));
				} catch (\Supra\Search\Exception\RuntimeException $e) {
					$results = $e;
				}
			}
		}

		$response->getContext()
				->setValue(self::ADDITIONAL_RESPONSE_DATA_KEY_RESULTS, $results);

		return $results;
	}

	/**
	 * Loads property definition array
	 * @return array
	 */
	public function getPropertyDefinition()
	{
		$contents = array();

		$types = array(
			self::BLOCK_TYPE_FORM => 'Form',
			self::BLOCK_TYPE_RESULTS => 'Results'
		);

		$blockTypesForSelect = array();
		foreach ($types as $id => $value) {
			/* @var $type BannerTypeAbstraction */

			$blockTypesForSelect[$id] = $value;
		}

		$html = new \Supra\Editable\Select('Block type');
		$html->setValues($blockTypesForSelect);
		$html->setDefaultValue($id);

		$contents[self::PROPERTY_NAME_BLOCK_TYPE] = $html;

		return $contents;
	}

	/**
	 * @param string $text
	 * @return array
	 */
	private function doSearch($text, $maxRows, $startRow)
	{
		$searchService = new SearchService();

		$searchRequest = new PageLocalizationSearchRequest();

		$lm = ObjectRepository::getLocaleManager($this);

		$locale = $lm->getCurrent();

		$searchRequest->setResultMaxRows($maxRows);
		$searchRequest->setResultStartRow($startRow);
		$searchRequest->setText($text);
		$searchRequest->setLocale($locale);
		$searchRequest->setSchemaName(PageController::SCHEMA_PUBLIC);

		$searchResults = $searchService->processRequest($searchRequest);

		return $searchResults;
	}

	/* static function createController()
	  {
	  if (is_null(self::$instance)) {
	  self::$instance = parent::createController();
	  }

	  return self::$instance;
	  } */
}
