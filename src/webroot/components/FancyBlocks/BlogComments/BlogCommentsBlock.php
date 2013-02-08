<?php

namespace Project\FancyBlocks\BlogComments;

use Supra\Controller\Pages\BlockController;

class BlogCommentsBlock extends BlockController
{
	
	/**
	 * @return array
	 */
	public static function getPropertyDefinition()
	{
		$properties = array();

		return $properties;
	}

	protected function doExecute()
	{
		$response = $this->getResponse();
		/* @var $response \Supra\Response\TwigResponse */

		// FIXME
		$response->assign('commentCount', 2);
        
		$response->outputTemplate('index.html.twig');
	}
	
}
