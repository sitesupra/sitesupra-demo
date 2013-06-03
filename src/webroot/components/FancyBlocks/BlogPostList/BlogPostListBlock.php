<?php

namespace Project\FancyBlocks\BlogPostList;

use Supra\Controller\Pages\BlockController;
use Supra\Controller\Pages\Entity\ApplicationPage;
use Supra\Controller\Pages\Blog\BlogApplication;

class BlogPostListBlock extends BlockController
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
		$response->assign('totalPages', 20);
		$response->assign('currentPage', 19); // 0 index
		$response->outputTemplate('index.html.twig');
	}
}
