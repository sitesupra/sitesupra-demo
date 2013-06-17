<?php

namespace Project\FancyBlocks\BlogPostListShort;

use Project\FancyBlocks\BlogPostList\BlogPostListBlock;
use Supra\Controller\Pages\Entity\ApplicationPage;

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
use Supra\Controller\Pages\Entity\ReferencedElement\LinkReferencedElement;

class BlogPostListShortBlock extends BlogPostListBlock
{
	/**
	 * @var \Supra\Controller\Pages\Blog\BlogApplication
	 */
	protected $blogApplication;
	
	
	protected function doExecute()
	{
        $postData = array();
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
		
        
		if ($postsCount > 0) {
			
			$localizations = $qb->setMaxResults($postsPerPage)
                    ->orderBy('l.creationTime', 'DESC')
                    ->getQuery()
                    ->getResult();
			
			$localizationIds = \Supra\Database\Entity::collectIds($localizations);

			$propertyMap = array();			
			$propertyFinder = new Finder\BlockPropertyFinder($localizationFinder);
			$propertyFinder->addFilterByComponent(BlogPostBlock::CN(), array(self::PROPERTY_DESCRIPTION, self::PROPERTY_MEDIA));

			$propertyQb = $propertyFinder->getQueryBuilder();
			$properties = $propertyQb->andWhere('l.id IN (:ids)')
                    ->setParameter('ids', $localizationIds)
                    ->getQuery()
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
		
		$response->assign('posts', $postData)
                ->outputTemplate('index.html.twig');
	}
    
    
    protected function getBlogApplication()
    {
            
		if ($this->blogApplication === null) {
            
            $blogPage = $this->getPropertyValue('blog_page');
            
            if ($blogPage instanceof LinkReferencedElement) {
                $localization = $blogPage->getPageLocalization();
                
                if ($localization instanceof ApplicationLocalization) {

                    $em = \Supra\ObjectRepository\ObjectRepository::getEntityManager($this);
                    $application = PageApplicationCollection::getInstance()
                        ->createApplication($localization, $em);

                    if ($application instanceof BlogApplication) {
                        $this->blogApplication = $application;
                    }
                }
            }
		}
		
		return $this->blogApplication;
    }
}