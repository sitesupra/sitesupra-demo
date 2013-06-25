<?php

namespace Project\FancyBlocks\BlogArchive;

use Supra\Controller\Pages\BlockController;

class BlogArchiveBlock extends BlockController
{
    public function doExecute()
    {
        $response = $this->getResponse();
        
        $response->assign('archiveLastYear', array(
            array('title' => 'June 2013', 'path'  => '/...'),
            array('title' => 'May 2013', 'path'  => '/...'),
            array('title' => 'April 2013', 'path'  => '/...'),
            array('title' => 'March 2013', 'path'  => '/...'),
            array('title' => 'February 2013', 'path'  => '/...'),
            array('title' => 'January 2013', 'path'  => '/...'),
            array('title' => 'December 2012', 'path'  => '/...'),
            array('title' => 'November 2012', 'path'  => '/...'),
            array('title' => 'October 2012', 'path'  => '/...'),
            array('title' => 'September 2012', 'path'  => '/...'),
            array('title' => 'August 2012', 'path'  => '/...'),
            array('title' => 'July 2012', 'path'  => '/...'),
        ));
        
        $response->assign('archiveAll', array(
            array('title' => 'June 2013', 'path'  => '/...'),
            array('title' => 'May 2013', 'path'  => '/...'),
            array('title' => 'April 2013', 'path'  => '/...'),
            array('title' => 'March 2013', 'path'  => '/...'),
            array('title' => 'February 2013', 'path'  => '/...'),
            array('title' => 'January 2013', 'path'  => '/...'),
            array('title' => 'December 2012', 'path'  => '/...'),
            array('title' => 'November 2012', 'path'  => '/...'),
            array('title' => 'October 2012', 'path'  => '/...'),
            array('title' => 'September 2012', 'path'  => '/...'),
            array('title' => 'August 2012', 'path'  => '/...'),
            array('title' => 'July 2012', 'path'  => '/...'),
            array('title' => 'June 2012', 'path'  => '/...'),
            array('title' => 'May 2012', 'path'  => '/...'),
        ));
        
        $response->assign('currentPeriod', 'April 2013');
        
        $response->outputTemplate('index.html.twig');
    }
}
            
        