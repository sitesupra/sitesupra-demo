<?php

namespace Project\FancyBlocks\Social;

use Supra\Controller\Pages\BlockController;
use Supra\Editable;

class SocialBlock extends BlockController
{

	/**
	 * @return array
	 */
	public static function getPropertyDefinition()
	{
		$properties = array();

		$rssLink = new Editable\Link('Rss link');
		$properties['rss'] = $rssLink;

		$faceBookLink = new Editable\Link('Facebook link');
		$properties['facebook'] = $faceBookLink;

		$flickrLink = new Editable\Link('Flickr link');
		$properties['flickr'] = $flickrLink;

		$youtubeLink = new Editable\Link('Youtube link');
		$properties['youtube'] = $youtubeLink;

		return $properties;
	}

	protected function doExecute()
	{
		$response = $this->getResponse();
		/* @var $response \Supra\Response\TwigResponse */

		$response->outputTemplate('index.html.twig');
	}

}
