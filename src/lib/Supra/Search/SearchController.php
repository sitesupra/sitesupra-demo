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
use Supra\Controller\Pages\Search\PageLocalizationSearchResultSet;
use Supra\Controller\Pages\Search\PageLocalizationSearchResultItem;

/**
 * Simple text block
 */
class SearchController extends BlockController
{
	const RESPONSE_CONTEXT_KEY_RESULTS = 'search-results';

	/**
	 * @param Request\RequestInterface $request
	 * @return Response\TwigResponse
	 */
	public function createResponse(Request\RequestInterface $request)
	{
		$response = parent::createResponse($request);

		if ( ! $this->getConfiguration()->localTemplateDirectory) {
			$response->setLoaderContext(null);
		}

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
		$response = $this->getResponse();
		$configuration = $this->getConfiguration();
		$results = $this->getResults();

		if ($results instanceof Exception\RuntimeException) {

			$response->assign('error', true);
			$response->outputTemplate($configuration->resultsTemplateFilename);
		} else if ($results instanceof PageLocalizationSearchResultSet) {

			$totalResultCount = $results->getTotalResultCount();

			if ($totalResultCount == 0) {
				
				$response->assign('resultCount', '0');
				$response->outputTemplate($configuration->noResultsTemplateFilename);
			} else {

				$em = ObjectRepository::getEntityManager($this);

				$results->gatherBreadcrumbs($em);

				$totalPages = ceil($results->getTotalResultCount() / $configuration->resultsPerPage);
				$response->assign('resultsPerPage', $configuration->resultsPerPage);
				$response->assign('searchResults', $results->getItems());
				$response->assign('pages', range(1, $totalPages));
				$response->assign('pageCount', $totalPages);
				$response->assign('resultCount', $results->getTotalResultCount());

				$response->outputTemplate($configuration->resultsTemplateFilename);
			}
		}
	}

	/**
	 * @return RuntimeException | PageLocalizationSearchResultSet
	 */
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
				->getValue(self::RESPONSE_CONTEXT_KEY_RESULTS);

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
		
		if (is_null($results)) {
			$results = new PageLocalizationSearchResultSet();
		}

		$response->getContext()
				->setValue(self::RESPONSE_CONTEXT_KEY_RESULTS, $results);

		return $results;
	}

	/**
	 * @param string $text
	 * @return PageLocalizationSearchResultSet
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

		$results = $searchService->processRequest($searchRequest);

		return $results;
	}

}
