<?php

namespace Project\FancyBlocks\BlogPostListShort;

use Supra\Controller\Pages\BlockController;
use Supra\Controller\Pages\Entity\ApplicationPage;
use Supra\Controller\Pages\Blog\BlogApplication;

class BlogPostListShortBlock extends BlockController
{
	/**
	 * @var \Supra\Controller\Pages\Blog\BlogApplication
	 */
	protected $blogApplication;
	
	
	protected function doExecute()
	{
		$response = $this->getResponse();
		/* @var $response \Supra\Response\TwigResponse */
		
		$response->assign('posts', array());
		$response->outputTemplate('index.html.twig');
	}
}
