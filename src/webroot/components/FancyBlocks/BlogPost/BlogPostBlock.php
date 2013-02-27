<?php

namespace Project\FancyBlocks\BlogPost;

use Supra\Controller\Pages\BlockController;

class BlogPostBlock extends BlockController
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

		$response->assign('tags', $this->getPostTags());
		
		// FIXME
		$response->assign('date', time());
		
		$response->outputTemplate('index.html.twig');
	}
	
	/**
	 * @return array
	 */
	protected function getPostTags()
	{
		$items = array();
		$tags = $this->getProperty('tags')->getValue();
		$tags = explode(';', $tags);
        
        foreach ($tags as $tag) {
            if ($tag) {
                $items[] = array(
                    'title' => $tag,
                    'url' => '#', // FIXME
                );
            }
        }
        
        return $items;
	}
	
}
