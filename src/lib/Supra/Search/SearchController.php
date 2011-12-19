<?php

namespace Supra\Search;

use Supra\Controller\Pages\BlockController;
use Supra\Request;
use Supra\Response;
use Supra\Search\SearchService;
use Supra\Controller\Pages\Search\PageLocalizationSearchRequest;
use Supra\Controller\Pages\PageController;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Uri\Path;
use Supra\Controller\Pages\BlockControllerCollection;

/**
 * Simple text block
 */
class SearchController extends BlockController
{

	public function createResponse(Request\RequestInterface $request)
	{
		$response = new Response\TwigResponse();
		
		return $response;
	}

	public function execute()
	{
		$request = $this->getRequest();
		$searchResults = array();
		$response = $this->getResponse();
		/* @var $response Response\TwigResponse */
		$q = $request->getQueryValue('q');

		if ( ! is_null($q)) {
			$response->assign('q', $q);
			$searchResults = $this->doSearch($q);
		}

		$path = $request->getPath();

		if ( ! empty($path)) {
			$resultUrl = $path->getFullPath(Path::FORMAT_BOTH_DELIMITERS);
			$response->assign('resultUrl', $resultUrl);
		}

		$response->assign('searchResults', $searchResults);

		$response->outputTemplate('template/' . $this->configuration->resultTemplateFilename);
	}

	public function setConfiguration(SearchControllerConfiguration $configuration)
	{
		parent::setConfiguration($configuration);
	}

	/**
	 * Loads property definition array
	 * @return array
	 */
	public function getPropertyDefinition()
	{
		$contents = array();

		return $contents;
	}

	private function doSearch($text)
	{
		$searchService = new SearchService();

		$searchRequest = new PageLocalizationSearchRequest();

		$lm = ObjectRepository::getLocaleManager($this);

		$locale = $lm->getCurrent();

		$searchRequest->setText($text);
		$searchRequest->setLocale($locale);
		$searchRequest->setSchemaName(PageController::SCHEMA_DRAFT);

		$searchResults = $searchService->processRequest($searchRequest);

		return $searchResults;
	}

}
