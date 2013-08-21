<?php

namespace Supra\Cms\BlogManager\Root;

use Supra\Cms\CmsAction;
use Supra\Response\TwigResponse;
use Supra\Request;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Entity\ApplicationPage;
use Supra\Controller\Pages\Entity\PageLocalization;
use Supra\Controller\Pages\Entity\ApplicationLocalization;
/**
 * @method TwigResponse getResponse()
 */
class RootAction extends CmsAction
{
    /**
     * @param Request\RequestInterface $request
     * @return TwigResponse
     */
    public function createResponse(Request\RequestInterface $request)
    {
        return $this->createTwigResponse();
    }
    
    public function indexAction()
    {
        $blogLocalizationId = $this->findBlogApplication();
        
        $this->getResponse()
                ->assign('blogLocalizationId', $blogLocalizationId)
                ->outputTemplate('blog-manager/root/root.html.twig');
    }
    
    
    public function findBlogApplication()
    {
        $blogAppId = null;
        $result = null;
        $blogLocalizationId = null;
        
        $em = ObjectRepository::getEntityManager($this);
        $qb = $em->createQueryBuilder();
        $result = $qb->select('ap')
                ->from(ApplicationPage::CN(), 'ap')
                ->where('ap.applicationId = :appType')
                ->setParameter('appType', 'blog')
                ->orderBy('ap.id', 'DESC')
                ->setMaxResults(1)
                ->getQuery()
                ->getResult();
        
        if (!empty($result)) {
            $blogApp = $result[0];
            if ($blogApp instanceof ApplicationPage) {
                
                $result = null;
                $blogAppId = $blogApp->getId();
                
                $qb = $em->createQueryBuilder();
                $result = $qb->select('l')
                        ->from(PageLocalization::CN(), 'l')
                        ->where('l.master = :blogAppId')
                        ->setParameter('blogAppId', $blogAppId)
                        ->getQuery()
                        ->getResult();
                
                if (!empty($result)) {
                    $blogLocalization = $result[0];
                    if ($blogLocalization instanceof ApplicationLocalization) {
                        $blogLocalizationId = $blogLocalization->getId();
                    }
                }
            }
        }
        
        return $blogLocalizationId;
    }

}