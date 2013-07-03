<?php

namespace Project\FancyBlocks\BlogArchive;

use Supra\Controller\Pages\BlockController;
use Supra\Controller\Pages\Blog\BlogApplication;

use Supra\Controller\Pages\Entity\ApplicationLocalization;
use Supra\Controller\Pages\Application\PageApplicationCollection;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Entity\Blog\BlogApplicationPostLocalization;
use Supra\Controller\Pages\Entity\PageLocalization;
use Supra\Controller\Pages\Entity\ReferencedElement\LinkReferencedElement;

class BlogArchiveBlock extends BlockController
{

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
        
		$period = $request->getQueryValue('period', null);
		$context->setValue(self::CONTEXT_PARAMETER_PERIOD, $period);
	}
    
    
    public function doExecute()
    {
        $response = $this->getResponse();
		/* @var $response \Supra\Response\TwigResponse */
        $context = $response->getContext(); 
        $application = $this->getBlogApplication();

        if ($application === null) {
            $response->outputTemplate('application-missing.html.twig');
            return;
        }
        
        
        $em = ObjectRepository::getEntityManager($this);
        $qb = $em->createQueryBuilder();
        $qb->select('bl.pageLocalizationId')
                ->from(BlogApplicationPostLocalization::CN(), 'bl')
                ->getQuery()
                ->getScalarResult();
        
        $blogPosts =  $qb->getQuery()->getResult();
        foreach($blogPosts as $blogPost) {
            $ids[] = $blogPost['pageLocalizationId'];
        }
        
        $qb = $em->createQueryBuilder()
                ->from(PageLocalization::CN(), 'p')
                ->where('p.id IN (:ids)')
                ->setParameter('ids', $ids);

        /* @var $application \Supra\Controller\Pages\Blog\BlogApplication */
        $archives = $application->getFilterFolders($qb, 'byYear');
        
        $currentPeriod = $context->getValue(self::CONTEXT_PARAMETER_PERIOD, null);
        
        $response->assign('archive', $archives)
                ->assign('currentPeriod', $currentPeriod)
                ->outputTemplate('index.html.twig');
    }
    
    
	/**
	 */
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