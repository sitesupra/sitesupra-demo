<?php

namespace Project\Blocks\NewsList;

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

class NewsListBlock extends BlockController
{

	const MAX_RESULTS = 3;
	
	/**
	 * @var array
	 */
	protected $yearList = array();

	/**
	 * @var integer
	 */
	protected $requestedYear;
	
	/**
	 * @return array
	 */
	public static function getPropertyDefinition()
	{
		return array();
	}
	
	/**
	 * @return LocalizationFinder
	 */
	private function getLocalizationFinder()
	{
		$em = ObjectRepository::getEntityManager($this);
		$request = $this->getRequest();
		$localization = $request->getPageLocalization();	
		
		$pageFinder = new PageFinder($em);
		
		$localizationFinder = new LocalizationFinder($pageFinder);
		$localizationFinder->addFilterByParent($localization, 1, 1);
		
		return $localizationFinder;
	}
	
	/**
	 * Get list of available years
	 * @return array
	 */
	private function getYearList()
	{
		$localizationFinder = $this->getLocalizationFinder();
		$qb = $localizationFinder->getQueryBuilder();
		
		$qb->select('DISTINCT l.creationYear')
				->orderBy('l.creationYear', 'DESC');
		$query = $qb->getQuery();
		$yearList = $query->getResult(ColumnHydrator::HYDRATOR_ID);
		
		return $yearList;
	}
	
	/**
	 * Return array of content block properties
	 * @param int $year
	 * @return array
	 */
	private function getPublications($year)
	{
		// Sanitize so it's OK to include in query inline
		$year = (int) $year;
		$localizationFinder = $this->getLocalizationFinder();
		$localizationFinder->addCustomCondition("l.creationYear = $year");
		
		$propertyFinder = new BlockPropertyFinder($localizationFinder);
		$propertyFinder->disableCache();
		$propertyFinder->addFilterByComponent('Project\Blocks\Text\TextController', array('content'));
		
		$qb = $propertyFinder->getQueryBuilder();
		$qb->orderBy('l.creationTime', 'DESC');
		$q = $qb->getQuery();
		$q->setMaxResults(self::MAX_RESULTS);
		$q->setFirstResult(max(0, (@$_GET['page'] - 1) * self::MAX_RESULTS));
		
		$paginator = new \Doctrine\ORM\Tools\Pagination\Paginator($q);
		
		return $paginator;
	}
	
	/**
	 * {@inheritdoc}
	 */
	protected function doPrepare()
	{
		$request = $this->getRequest();
		$response = $this->getResponse();
		
		if ( ! $request instanceof Request\HttpRequest) {
			return;
		}
		
		$yearList = $this->getYearList();
		$get = $request->getQuery();
		
		foreach ($yearList as $year) {
			$year = (int) $year;
			$this->yearList[$year] = array(
				'creationYear' => $year,
				'publications' => array(),
			);
		}

		$requestedYear = $get->has('year') && $get->isValid('year', 'smallint') ? 
				$get->getValid('year', 'smallint') : null;
		
		if ( ! array_key_exists($requestedYear, $this->yearList)) {
			$requestedYear = max($yearList);
		}
		
		$response->getContext()
				->setValue('breadcrumbs_year', $requestedYear);
		
		$this->requestedYear = $requestedYear;
	}

	protected function doExecute()
	{
		$request = $this->getRequest();
		$response = $this->getResponse();
		$pageLocalization = $request->getPageLocalization();

		foreach ($this->yearList as $year => &$yearData) {

			$yearData['active'] = ($this->requestedYear == $year);
			
			if ($year == $this->requestedYear) {
				
				$publications = $this->getPublications($year);
				
				foreach ($publications as $contentProperty) {
					
					/* @var $contentProperty BlockProperty */
					
					$publication = $contentProperty->getLocalization();
					
					if ( ! $publication instanceof PageLocalization) {
						// This is a template. Shouldn't happen.
						continue;
					}
					
					$id = $publication->getId();
					
					// fetch only one publication per page
					if (isset($yearData['publications'][$id])) {
						continue;
					}
					
					$title = $publication->getTitle();
					$date = $publication->getCreationTime();
					$path = $publication->getFullPath(Path::FORMAT_BOTH_DELIMITERS);
					
					$content = null;
					// skip content loading when in CMS mode
					if ($request instanceof PageRequestView) {
						$content = $this->getPublicationContent($contentProperty);
					}
					
					$yearData['publications'][$id] = array(
						'id' => $id,
						'title' => $title,
						'date' => $date,
						'path' => $path,
						'content' => $content,
					);
				}
				
//				die();
			}
		}

		$response->assign('yearList', $this->yearList);

		$response->getContext()
				->addCssLinkToLayoutSnippet('css', '/assets/css/page-news.css');
		
		if ($request instanceof PageRequestView) {
			$response->getContext()
					->addJsUrlToLayoutSnippet('js', '/assets/js/page-news.js');
		}

		$response->outputTemplate('news.html.twig');
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
