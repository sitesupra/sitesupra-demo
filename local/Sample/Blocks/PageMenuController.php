<?php

namespace Sample\Blocks;

use Supra\Package\Cms\Pages\BlockController;
use Supra\Package\Cms\Pages\Finder\PageFinder;

class PageMenuController extends BlockController
{
    public function doExecute()
    {
        $entityManager = $this->container->getDoctrine()
                ->getManager();
        /* @var $entityManager \Doctrine\ORM\EntityManager */

        $pageFinder = new PageFinder($entityManager);

        $pageFinder->addLevelFilter(1, 1);

        $this->getResponse()->render(array(
            'localizations' => $pageFinder->createLocalizationFinder()->getResult()
        ));
    }
}