<?php

namespace Project\FancyBlocks\NewsList;

use Supra\Controller\Pages\BlockController;
use Supra\Controller\Pages\Entity\PageLocalization;
use Supra\Controller\Pages\Entity\Abstraction\Localization;
use Supra\Controller\Pages\Request\PageRequestEdit;
use Supra\Controller\Pages\Request\PageRequestView;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Uri\Path;
use Supra\Controller\Pages\Entity\Abstraction\Block;
use Supra\Controller\Pages\Entity\BlockProperty;
use Supra\Request;
use Supra\Response;
use Supra\Database\Doctrine\Hydrator\ColumnHydrator;
use Supra\Controller\Pages\Finder\BlockPropertyFinder;
use Supra\Controller\Pages\Filter\ParsedHtmlFilter;
use Supra\Controller\Pages\Finder\PageFinder;
use Supra\Controller\Pages\Finder\LocalizationFinder;
use Doctrine\ORM\QueryBuilder;

class NewsListBlock extends BlockController
{

	/**
	 * @return array
	 */
	public static function getPropertyDefinition()
	{
		return array();
	}

	/**
	 * 
	 */
	protected function doExecute()
	{
		/* @var $request Request\HttpRequest */
		$request = $this->getRequest();
		$response = $this->getResponse();

		$firstPublicationData = array();
		$threePublicationsData = array();

		if ($request->getParameter('more', false) == false) {

			$publicationsProperties = $this->getFirstPublicationsProperties();

			$publicationsData = $this->getPublicationsData($publicationsProperties);

			if (count($publicationsData)) {
				$firstPublicationData = array_shift($publicationsData);
			}

			for ($i = 0; $i != 3; $i ++ ) {
				if (count($publicationsData)) {
					$threePublicationsData[] = array_shift($publicationsData);
				}
			}
		} else {

			$publicationsProperties = $this->getAllPublicationsProperties();

			$publicationsData = $this->getPublicationsData($publicationsProperties);
		}

		$response->assign('firstPublication', $firstPublicationData);
		$response->assign('threePublications', $threePublicationsData);
		$response->assign('publications', $publicationsData);

		/* @var $theme \Supra\Controller\Pages\Entity\Theme\Theme */
		$theme = $this->getRequest()->getLayout()->getTheme();

		$response->getContext()
				->addCssLinkToLayoutSnippet('css', $theme->getUrlBase() . '/assets/css/page-news.css');

		if ($request instanceof PageRequestView) {
			$response->getContext()
					->addJsUrlToLayoutSnippet('js', $theme->getUrlBase() . '/assets/js/page-news.js');
		}

		$response->outputTemplate('news.html.twig');
	}

	/**
	 * @param array $publicationsProperites
	 * @return array
	 */
	protected function getPublicationsData($publicationsProperites)
	{
		$publicationsData = array();

		foreach ($publicationsProperites as $publicationProperties) {
			/* @var $contentProperty BlockProperty */

			$image = null;
			$description = null;

			$templateCheckProperty = null;

			if ( ! empty($publicationProperties['description'])) {
				$description = $publicationProperties['description']->getValue();
				$templateCheckProperty = $publicationProperties['description'];
			}

			if ( ! empty($publicationProperties['image'])) {
				$image = $publicationProperties['image']->getValue();
				$templateCheckProperty = $publicationProperties['image'];
			}

			$publication = $templateCheckProperty->getLocalization();

			if ( ! $publication instanceof PageLocalization) {
				// This is a template. Shouldn't happen.
				continue;
			}

			$title = $publication->getTitle();
			$date = $publication->getCreationTime();
			$path = $publication->getFullPath(Path::FORMAT_BOTH_DELIMITERS);

			$publicationsData[] = array(
				'id' => $publication->getId(),
				'image' => $image,
				'title' => $title,
				'date' => $date,
				'path' => $path,
				'description' => $description,
			);
		}

		return $publicationsData;
	}

	/**
	 * @return LocalizationFinder
	 */
	private function getLocalizationFinder()
	{
		$em = ObjectRepository::getEntityManager($this);
		$request = $this->getRequest();

		/* @var $newsApplicationLink \Supra\Controller\Pages\Entity\ReferencedElement\LinkReferencedElement */
		$newsApplicationLink = $this->getPropertyValue('newsApplication');

		if ( ! empty($newsApplicationLink)) {
			$localization = $newsApplicationLink->getPage();
		} else {
			$localization = $request->getPageLocalization();
		}

		$pageFinder = new PageFinder($em);

		$localizationFinder = new LocalizationFinder($pageFinder);
		$localizationFinder->addFilterByParent($localization, 1, 1);

		return $localizationFinder;
	}

	/**
	 * @return QueryBuilder
	 */
	private function getPublicationPropertyFinderQueryBuilder()
	{
		$localizationFinder = $this->getLocalizationFinder();

		$propertyFinder = new BlockPropertyFinder($localizationFinder);
		$propertyFinder->addFilterByComponent('Project\FancyBlocks\NewsText\NewsTextBlock', array('description', 'image'));

		$qb = $propertyFinder->getQueryBuilder();
		$qb->orderBy('l.creationTime', 'DESC');

		return $qb;
	}

	/**
	 * @param array $queryResults
	 * @return array
	 */
	private function processPublicationPropertyFinderResults($queryResults)
	{
		$items = array();

		foreach ($queryResults as $queryResult) {
			/* @var $queryResult BlockProperty */

			$localizationId = $queryResult->getLocalization()->getId();

			if (empty($items[$localizationId])) {
				$items[$localizationId] = array();
			}

			$item = &$items[$localizationId];

			$item[$queryResult->getName()] = $queryResult;
		}

		return $items;
	}

	/**
	 * Return array of content block properties
	 * @param int $year
	 * @return array
	 */
	private function getAllPublicationsProperties()
	{
		$qb = $this->getPublicationPropertyFinderQueryBuilder();

		$queryResults = $qb->getQuery()->getResult();

		$publicatiosnProperties = $this->processPublicationPropertyFinderResults($queryResults);

		return $publicatiosnProperties;
	}

	/**
	 * Return array of content block properties
	 * @param int $year
	 * @return array
	 */
	private function getFirstPublicationsProperties()
	{
		$qb = $this->getPublicationPropertyFinderQueryBuilder();

		$qb->setMaxResults(10 * 2);

		$queryResults = $qb->getQuery()->getResult();

		$publicationsProperties = $this->processPublicationPropertyFinderResults($queryResults);

		return $publicationsProperties;
	}

	/**
	 * {@inheritdoc}
	 */
	protected function doPrepare()
	{
		$request = $this->getRequest();

		if ( ! $request instanceof Request\HttpRequest) {
			return;
		}
	}

	/**
	 * @param BlockProperty $contentProperty
	 * @return string
	 */
	protected function getPublicationContent(BlockProperty $contentProperty)
	{
		$parser = new ParsedHtmlFilter();
		$parser->property = $contentProperty;
		$html = $parser->filter($contentProperty->getValue());

		return $html;
	}

}
