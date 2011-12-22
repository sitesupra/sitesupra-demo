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
	
	public function execute()
	{
		$blockType = $this->getPropertyValue(self::PROPERTY_NAME_BLOCK_TYPE);
		
		if ($blockType == self::BLOCK_TYPE_FORM) {

			//$this->response = $this->formResponse;
			//$this->getResults();
			$this->executeForm();
		}
		else if ($blockType == self::BLOCK_TYPE_RESULTS) {

			//$this->response = $this->resultsResponse;
			//$this->getResults();
			$this->executeResults();
		}
	}

	public function executeForm()
	{
		$response = $this->getResponse();

		$results = $this->getResults();

		if ( ! empty($results)) {

			$response->assign('haveResults', true);
			$response->assign('resultCount', count($results));
		}

		$response->outputTemplate('template/' . $this->configuration->formTemplateFilename);
	}

	protected function executeResults()
	{
		$response = $this->getResponse();

		$results = $this->getResults();
		
		if(empty($results)) {
			$response->outputTemplate('template/' . $this->configuration->noResultsTemplateFilename);
		}
		else {
		
			$em = ObjectRepository::getEntityManager($this);

			$pr = $em->getRepository(Page::CN());

			foreach($results as &$result) {

				$result['breadcrumbs'] = array();	

				$ancestorIds = array_reverse($result['ancestorIds']);

				foreach($ancestorIds as $ancestorId) {

						$p = $pr->find($ancestorId);

						if($p instanceof Page) {

							$pl = $p->getLocalization($result['localeId']);
							$result['breadcrumbs'][] = $pl->getTitle();
						}
						else if($p instanceof PageLocalization) {
							$result['breadcrumbs'][] = $p->getTitle();
						}
						elseif($p instanceof \Supra\Controller\Pages\Entity\GroupPage) {
							$result['breadcrumbs'][] = $p->getTitle();
						}
				}
			}

			$response->assign('searchResults', $results);

			$response->outputTemplate('template/' . $this->configuration->resultsTemplateFilename);
		}
	}

	protected function getResults()
	{
		$request = $this->getRequest();

		$response = $this->getResponse();
		/* @var $response Response\TwigResponse */

		$q = $request->getQueryValue('q');
		$response->assign('q', $q);
		
		$results = $response->getAdditionalDataItem(self::ADDITIONAL_RESPONSE_DATA_KEY_RESULTS);

		if ( ! is_null($results)) {
			return $results;
		}

		if ($request instanceof PageRequestView) {

			if ( ! is_null($q)) {
				$response->assign('q', $q);
				$results = $this->doSearch($q);
			}

			$path = $request->getPath();

			if ( ! empty($path)) {
				$resultUrl = $path->getFullPath(Path::FORMAT_BOTH_DELIMITERS);
				$response->assign('resultUrl', $resultUrl);
			}
		}

		$response->setAdditionalDataItem(self::ADDITIONAL_RESPONSE_DATA_KEY_RESULTS, $results);
		
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
	private function doSearch($text)
	{
		$searchService = new SearchService();

		$searchRequest = new PageLocalizationSearchRequest();

		$lm = ObjectRepository::getLocaleManager($this);

		$locale = $lm->getCurrent();

		$searchRequest->setResultMaxRows(1000);
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
