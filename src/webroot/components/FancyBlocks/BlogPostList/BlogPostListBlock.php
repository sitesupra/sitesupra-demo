<?php

namespace Project\FancyBlocks\BlogPostList;

use Supra\Controller\Pages\BlockController;
use Supra\Controller\Pages\Blog\BlogApplication;
use Project\FancyBlocks\BlogPost\BlogPostBlock;
use Supra\Controller\Pages\Entity\BlockProperty;
use Supra\Controller\Pages\Request\PageRequestEdit;
use Supra\Controller\Pages\Filter\InlineMediaFilter;
use Supra\Controller\Pages\Filter\EditableInlineMedia;

use Supra\Controller\Pages\Entity\ApplicationLocalization;
use Supra\Controller\Pages\Finder;
use Supra\Controller\Pages\Application\PageApplicationCollection;
use Supra\Controller\Pages\Markup;

class BlogPostListBlock extends BlockController
{	
	const PROPERTY_MEDIA = 'media',
		   PROPERTY_CONTENT = 'content';
	
	const LIMIT_CONTENT_LENGTH = 200;
	
	const CONTEXT_PARAMETER_PAGE = '__blogListPage';
	const CONTEXT_PARAMETER_TAG = '__blogListTag';
    const CONTEXT_PARAMETER_PERIOD = '__blogArchivePeriod';
	
	/**
	 * @var \Supra\Controller\Pages\Blog\BlogApplication
	 */
	protected $blogApplication;
	
	
	protected function doPrepare()
	{
		$request = $this->getRequest();
		$context = $this->getResponse()
				->getContext();
		
		$page = $request->getQuery()
				->getValidIfExists('page', \Supra\Validator\Type\AbstractType::INTEGER);
		$pageIndex = ( ! empty($page) ? $page : 0);
		
		$tag = $request->getQueryValue('tag', null);
        $period = $request->getQueryValue('period', null);
		
		$context->setValue(self::CONTEXT_PARAMETER_PAGE, $pageIndex);
		$context->setValue(self::CONTEXT_PARAMETER_TAG, $tag);
        $context->setValue(self::CONTEXT_PARAMETER_PERIOD, $period);
	}
	
	/**
	 * @TODO: allow to set blog app using Link property
	 */
	protected function doExecute()
	{
		$response = $this->getResponse();
		$context = $response->getContext();
		/* @var $response \Supra\Response\TwigResponse */
		
		$application = $this->getBlogApplication();
		
		if ($application === null) {
			$response->outputTemplate('application-missing.html.twig');
			return;
		}
		
		$page = $application->getApplicationLocalization()
				->getMaster();
		
		$pageFinder = new Finder\PageFinder($application->getEntityManager());
		$pageFinder->addFilterByParent($page);
		
		$localizationFinder = new Finder\LocalizationFinder($pageFinder);
		$qb = $localizationFinder->getQueryBuilder();

		$request = $this->getRequest();
		/* @var $request \Supra\Request\HttpRequest */
		$tag = $context->getValue(self::CONTEXT_PARAMETER_TAG, null);
		
		// apply tag filter
		if ( ! empty($tag)) {
			$qb->addSelect('lt')
					->join('l.tags', 'lt')
					->andWhere('lt.name = :tagName')
					->setParameter('tagName', $tag);
		}
		
		$selectedPeriod = $context->getValue(self::CONTEXT_PARAMETER_PERIOD, null);
		if ( ! empty($selectedPeriod)) {

			$selectedMonth = \DateTime::createFromFormat('!F Y', $selectedPeriod);
			
			if ($selectedMonth instanceof \DateTime) {
				$nextMonth = clone $selectedMonth;
				$nextMonth->modify('+1 month');
				 
				$qb->andWhere('l.creationTime >= :start AND l.creationTime <= :end')
						->setParameter('start', $selectedMonth)
						->setParameter('end', $nextMonth);
				
				//
				$response->assign('period', $selectedPeriod);
			}
		}
		
		$postsCount = $localizationFinder->getTotalCount($qb, 'l.id');
		$pageIndex = $context->getValue(self::CONTEXT_PARAMETER_PAGE, 0);
		$postsPerPage = $this->getPropertyValue('posts_per_page');
		
		$postData = array();
		
		if ($postsCount > 0) {
            
			$offset = $postsPerPage * $pageIndex;
			
			$qb->setFirstResult($offset);
			$qb->setMaxResults($postsPerPage);
			
			$qb->orderBy('l.creationTime', 'DESC');
			
            $query = $qb->getQuery();
            
			$localizations = $query->getResult();
			
			$localizationIds = \Supra\Database\Entity::collectIds($localizations);

			$propertyMap = array();
			
			$propertyFinder = new Finder\BlockPropertyFinder($localizationFinder);
			$propertyFinder->addFilterByComponent($this->getBlogPostBlockClass(), 
					array_merge(array(self::PROPERTY_CONTENT, self::PROPERTY_MEDIA), $this->getAdditionalPostProperties())
			);

			$propertyQb = $propertyFinder->getQueryBuilder();
			$propertyQb->andWhere('l.id IN (:ids)')
					->setParameter('ids', $localizationIds);
			
			// @FIXME: useResultCache
			$properties = $propertyQb->getQuery()
					->getResult();
			
			foreach ($properties as $property) {
				$localizationId = $property->getLocalization()
						->getId();
				
				if ( ! isset($propertyMap[$localizationId])) {
					$propertyMap[$localizationId] = array();
				}
				
				$postProperties = &$propertyMap[$localizationId];
				
				$filteredValue = $this->getFilteredPropertyValue($property);
				
				$postProperties[$property->getName()] = $filteredValue;
			}
			
			foreach ($localizations as $localization) {
				
				$localizationId = $localization->getId();
				
				$postData[] = array(
					'localization' => $localization,
					'properties' => (isset($propertyMap[$localizationId]) ? $propertyMap[$localizationId] : array()),
				);
			}
		}
				
		$totalPages = (int) ceil($postsCount / $postsPerPage);
		
		$response->assign('posts', $postData)
				->assign('totalPages', $totalPages)
				->assign('currentPage', $pageIndex)
				->assign('blogPagePath', $application->getApplicationLocalization()->getPath())
				->assign('currentTag', $tag)
				->outputTemplate('index.html.twig');
	}
	
	/**
	 * @param \Supra\Controller\Pages\Entity\BlockProperty $property
	 * @return mixed
	 */
	protected function getFilteredPropertyValue(BlockProperty $property)
	{
		$editable = $property->getEditable();
		
		if ($editable instanceof \Supra\Editable\InlineMedia) {

			$filter = new InlineMediaFilter;
			$filter->property = $property;
			
			$editable->addFilter($filter);
		}
		
		if ($editable instanceof \Supra\Editable\Html) {
			$filteredValue = $editable->getFilteredValue();
			return $this->getTruncatedHtmlContent($filteredValue, self::LIMIT_CONTENT_LENGTH);
		}
		
		return $editable->getFilteredValue();
	}
	
	/**
	 */
	protected function getBlogApplication()
	{
		if ($this->blogApplication === null) {
			$request = $this->getRequest();
			/* @var $request PageRequest */
			
			$localization = $request->getPageLocalization();			
			
			if ($localization instanceof ApplicationLocalization) {
				
				$em = \Supra\ObjectRepository\ObjectRepository::getEntityManager($this);
				
				$application = PageApplicationCollection::getInstance()
					->createApplication($localization, $em);
				
				if ($application instanceof BlogApplication) {
					$this->blogApplication = $application;
				}
			}
		}
		
		return $this->blogApplication;
	}
	
	protected function getTruncatedHtmlContent($content, $length)
	{
		if (empty($content)) {
			return null;
		}
		
		if (is_array($content)) {
			if ( ! isset($content['html'])) {
				return null;
			}
			
			$content = $content['html'];
		}
		
		$tokenizer = new Markup\DefaultTokenizer($content);
		$tokenizer->tokenize();
				
		$elements = $tokenizer->getElements();
		
		$result = null;
		
		foreach ($elements as $element) {
			if ($element instanceof Markup\HtmlElement) {
				$result .= $element->getContent();
			}
		}

		if ( ! empty($result)) {

			$result = strip_tags($result);
			
			if (mb_strlen($result) > $length) {
				if (false !== ($breakpoint = mb_strpos($result, ' ', $length))) {
					$length = $breakpoint;
				}
				
				return new \Twig_Markup(rtrim(mb_substr($result, 0, $length)) . '...', 'UTF-8');
            }

            return new \Twig_Markup($result, 'UTF-8');
        }
		
		return null;
	}
	
	/**
	 * Blog Post block class name getter
	 * Value used in property finder, extracted in separate method so it could be extended
	 * 
	 * @return string
	 */
	protected function getBlogPostBlockClass()
	{
		return BlogPostBlock::CN();
	}
	
	/**
	 * Extend this function to add additional properties you want to be found by property finder
	 * @return array
	 */
	protected function getAdditionalPostProperties()
	{
		return array();
	}
}

