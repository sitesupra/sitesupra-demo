<?php

namespace Project\Search;

use Supra\Controller\Pages\BlockController;
use Supra\Request;
use Supra\Response;
use Supra\Search\SearchService;
use Supra\Controller\Pages\Search\PageLocalizationSearchRequest;
use Supra\Controller\Pages\PageController;
use Supra\ObjectRepository\ObjectRepository;

/**
 * Simple text block
 */
class SearchController extends BlockController
{

	public function execute()
	{
		$request = $this->getRequest();

		$searchResults = array();

		$response = $this->getResponse();
		/* @var $response Response\TwigResponse */

		if ( ! is_null($request->getQueryValue('q'))) {

			$q = $request->getQueryValue('q');

			$response->assign('q', $q);

			$searchResults = $this->doSearch($q);
		}


		// DEV comment about the block
		$block = $this->getBlock();
		$comment = '';
		if ( ! empty($block)) {
			$comment .= "Block $block.\n";
			if ($block->getLocked()) {
				$comment .= "Block is locked.\n";
			}
			if ($block->getPlaceHolder()->getLocked()) {
				$comment .= "Place holder is locked.\n";
			}
			$comment .= "Master " . $block->getPlaceHolder()->getMaster()->__toString() . ".\n";
		}

		$response->assign('title', $comment);

		$path = $request->getPath();
		
		if( ! empty($path)) {
			$response->assign('resultUrl', $request->getPath()->getFullPath());
		}

		$response->assign('searchResults', $searchResults);

		// Local file is used
		$response->outputTemplate('index.html.twig');
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
		$searchRequest->setSchemaName(PageController::SCHEMA_PUBLIC);

		$searchResults = $searchService->processRequest($searchRequest);

		return $searchResults;
	}

}
