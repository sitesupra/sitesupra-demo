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

class BlogPostListBlock extends BlockController
{	
	
	const PROPERTY_DESCRIPTION = 'description',
	       PROPERTY_MEDIA = 'media',
           PROPERTY_STYLE = 'style';
	
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
		
		$postsCount = $localizationFinder->getTotalCount($qb, 'l.id');
		$postsPerPage = $this->getPropertyValue('posts_per_page');
		
		$pageIndex = $context->getValue(self::CONTEXT_PARAMETER_PAGE, 0);
        $selectedPeriod = $context->getValue(self::CONTEXT_PARAMETER_PERIOD, null);
		
		$postData = array();
		
		if ($postsCount > 0) {
            
            if ($selectedPeriod) {
                $thisMonth = new \DateTime();
                $timeStamp = strtotime($selectedPeriod);
                if ($timeStamp) {
                    $thisMonth->setTimestamp($timeStamp);
                    $nextMonth = clone $thisMonth;
                    $nextMonth->modify('+1 month');
                    $qb->andWhere('l.creationTime >= :monthStart')
                            ->andWhere('l.creationTime <= :monthEnd')
                            ->setParameter('monthStart', $thisMonth)
                            ->setParameter('monthEnd', $nextMonth);
                }
            }
            
			$offset = $postsPerPage * $pageIndex;
			
			$qb->setFirstResult($offset);
			$qb->setMaxResults($postsPerPage);
			
			$qb->orderBy('l.creationTime', 'DESC');
			
            $query = $qb->getQuery();
            $sql = $query->getSQL();
            
			$localizations = $query->getResult();
			
			$localizationIds = \Supra\Database\Entity::collectIds($localizations);

			$propertyMap = array();
			
			$propertyFinder = new Finder\BlockPropertyFinder($localizationFinder);
			$propertyFinder->addFilterByComponent(BlogPostBlock::CN(), array(self::PROPERTY_DESCRIPTION, self::PROPERTY_MEDIA, self::PROPERTY_STYLE));

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
			$filter = ($this->getRequest() instanceof PageRequestEdit ? new EditableInlineMedia : new InlineMediaFilter);
			$filter->property = $property;
			
			$editable->addFilter($filter);
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
}
