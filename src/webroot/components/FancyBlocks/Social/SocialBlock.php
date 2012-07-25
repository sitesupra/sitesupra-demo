<?php

namespace Project\FancyBlocks\Social;

use Supra\Controller\Pages\BlockController;
use Supra\Editable;

class SocialBlock extends BlockController
{

	public static function getPropertyDefinition()
	{
		$properties = array();

		$link = new Editable\Link('Rss link');
		$properties['rss'] = $link;
		
		$link = new Editable\Link('Facebook link');
		$properties['facebook'] = $link;
		
		$link = new Editable\Link('Flickr link');
		$properties['flickr'] = $link;
		
		$link = new Editable\Link('Youtube link');
		$properties['youtube'] = $link;
		
		return $properties;
	}

	protected function doExecute()
	{
		$request = $this->getRequest();
		/* @var $request \Supra\Request\HttpRequest */
		$response = $this->getResponse();
		/* @var $response \Supra\Response\TwigResponse */

		// code

		$response->outputTemplate('index.html.twig');
	}

}
